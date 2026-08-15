<?php

namespace App\Services;

use App\Exceptions\PostingPeriodClosedException;
use App\Models\ChartOfAccount;
use App\Models\FinancialPeriod;
use App\Models\FinanceSetting;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\PostingRule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Configurable accounting rules engine. Every module posts financial events
 * through post($eventKey, $context) instead of calling FinancialService
 * directly, so which accounts get hit is driven by fin_posting_rules rows
 * (editable via the Finance > Chart of Accounts area) rather than code.
 *
 * Journal Entries are a document layer on top of the existing flat
 * fin_ledger_entries ledger — posting a journal still writes through
 * FinancialService::recordTransaction() using the original caller's
 * reference_type/reference_id, so existing reversal-lookup code in
 * ExpenseObserver/InvoiceObserver/PaymentObserver (which queries
 * LedgerEntry by reference_type/reference_id) keeps working unchanged.
 */
class PostingEngineService
{
    public function __construct(protected FinancialService $financialService)
    {
    }

    /**
     * Resolve the configured rule for an event, build balanced journal
     * lines, and create the Draft journal (auto-posting it immediately
     * unless posting_engine_auto_post is disabled for the company).
     * Never throws for a missing/inactive rule — logs and returns null,
     * matching the try/catch-and-log behavior every existing caller uses.
     */
    public function post(string $eventKey, array $context): ?JournalEntry
    {
        $companyId = $context['company_id'] ?? 1;

        $rule = PostingRule::resolveFor($eventKey, $companyId);

        if (! $rule) {
            Log::warning("PostingEngineService: no posting rule configured for event [{$eventKey}].");

            return null;
        }

        $date = isset($context['date']) ? Carbon::parse($context['date']) : now();

        $period = $companyId ? FinancialPeriod::openPeriodCovering($companyId, $date) : null;
        if ($period && in_array($period->status, ['Closed', 'Locked'], true)) {
            throw new PostingPeriodClosedException(
                "Cannot post to period [{$period->name}] — it is {$period->status}."
            );
        }

        $lines = $rule->resolver_class
            ? app($rule->resolver_class)->resolveLines($context)
            : $this->buildFixedLines($rule, $context);

        $totalDebit = round(array_sum(array_column($lines, 'debit')), 2);
        $totalCredit = round(array_sum(array_column($lines, 'credit')), 2);

        if (empty($lines) || abs($totalDebit - $totalCredit) >= 0.01) {
            Log::error("PostingEngineService: rule for event [{$eventKey}] produced unbalanced lines (debit={$totalDebit}, credit={$totalCredit}).");

            return null;
        }

        $referenceType = $context['reference_type'] ?? $eventKey;
        $referenceId = (string) ($context['reference_id'] ?? '');
        $description = $context['description'] ?? $rule->description_template ?? $eventKey;

        $entry = DB::transaction(function () use ($companyId, $date, $eventKey, $description, $period, $lines, $context) {
            $journal = JournalEntry::create([
                'company_id' => $companyId,
                'date' => $date->toDateString(),
                'reference' => $context['reference'] ?? null,
                'source_module' => explode('.', $eventKey)[0],
                'description' => $description,
                'currency' => $context['currency'] ?? 'KES',
                'status' => 'Draft',
                'created_by' => auth()->id(),
                'financial_period_id' => $period?->id,
            ]);

            $this->writeLines($journal, $lines, $context);

            return $journal;
        });

        if (FinanceSetting::getSetting($companyId, 'posting_engine_auto_post', true)) {
            $this->finalizePost($entry, $lines, $referenceType, $referenceId);
        }

        return $entry->fresh();
    }

    /**
     * Post a Draft journal that was created manually (e.g. via the Finance
     * UI) rather than through post(). Uses its own id as the ledger
     * reference since there's no originating business record.
     */
    public function postJournal(JournalEntry $entry): JournalEntry
    {
        if (! $entry->canPost()) {
            throw new RuntimeException('Journal entry cannot be posted — it must be a balanced Draft.');
        }

        if ($entry->company_id) {
            $period = FinancialPeriod::openPeriodCovering($entry->company_id, $entry->date);
            if ($period && in_array($period->status, ['Closed', 'Locked'], true)) {
                throw new PostingPeriodClosedException(
                    "Cannot post to period [{$period->name}] — it is {$period->status}."
                );
            }
        }

        $lines = $entry->lines->map(fn (JournalLine $line) => [
            'account_code' => $line->account->account_code,
            'debit' => (float) $line->debit,
            'credit' => (float) $line->credit,
        ])->all();

        $this->finalizePost($entry, $lines, 'journal_entry', (string) $entry->id);

        return $entry->fresh();
    }

    public function reverseJournal(JournalEntry $entry, string $reason = 'Reversal'): JournalEntry
    {
        if (! $entry->canReverse()) {
            throw new RuntimeException('Only a Posted journal entry can be reversed.');
        }

        if (! $entry->ledger_transaction_id) {
            throw new RuntimeException('Journal entry has no ledger transaction to reverse.');
        }

        return DB::transaction(function () use ($entry, $reason) {
            $newTransactionId = $this->financialService->reverseTransaction($entry->ledger_transaction_id, $reason);

            $reversal = JournalEntry::create([
                'company_id' => $entry->company_id,
                'date' => now()->toDateString(),
                'reference' => $entry->journal_number,
                'source_module' => $entry->source_module,
                'description' => "Reversal of {$entry->journal_number}: {$reason}",
                'currency' => $entry->currency,
                'status' => 'Posted',
                'created_by' => auth()->id(),
                'posted_by' => auth()->id(),
                'reversed_journal_id' => $entry->id,
                'ledger_transaction_id' => $newTransactionId,
            ]);

            $lineNo = 1;
            foreach ($entry->lines as $line) {
                JournalLine::create([
                    'journal_entry_id' => $reversal->id,
                    'account_id' => $line->account_id,
                    'cost_centre_id' => $line->cost_centre_id,
                    'profit_centre_id' => $line->profit_centre_id,
                    'project_id' => $line->project_id,
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                    'description' => "Reversal: {$line->description}",
                    'line_no' => $lineNo++,
                ]);
            }

            $entry->update(['status' => 'Reversed']);

            return $reversal;
        });
    }

    public function cancel(JournalEntry $entry): JournalEntry
    {
        if (! $entry->canCancel()) {
            throw new RuntimeException('Only a Draft journal entry can be cancelled.');
        }

        $entry->update(['status' => 'Cancelled']);

        return $entry;
    }

    protected function buildFixedLines(PostingRule $rule, array $context): array
    {
        $amount = round((float) ($context['amount'] ?? 0), 2);
        $lines = [];

        if ($rule->debit_account_code) {
            $lines[] = [
                'account_code' => $rule->debit_account_code,
                'debit' => $amount,
                'credit' => 0,
                'wallet_type' => $rule->debit_wallet_type,
            ];
        }

        if ($rule->credit_account_code) {
            $lines[] = [
                'account_code' => $rule->credit_account_code,
                'debit' => 0,
                'credit' => $amount,
                'wallet_type' => $rule->credit_wallet_type,
            ];
        }

        return $lines;
    }

    protected function writeLines(JournalEntry $journal, array $lines, array $context): void
    {
        $lineNo = 1;

        foreach ($lines as $line) {
            $account = ChartOfAccount::resolveByCode($journal->company_id, $line['account_code']);

            if (! $account) {
                throw new RuntimeException("PostingEngineService: no Chart of Accounts entry for code [{$line['account_code']}].");
            }

            JournalLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $account->id,
                'cost_centre_id' => $context['cost_centre_id'] ?? null,
                'profit_centre_id' => $context['profit_centre_id'] ?? null,
                'project_id' => $context['project_id'] ?? null,
                'debit' => $line['debit'] ?? 0,
                'credit' => $line['credit'] ?? 0,
                'description' => $journal->description,
                'line_no' => $lineNo++,
            ]);
        }
    }

    /**
     * Write the balanced lines through to the flat ledger via
     * FinancialService (preserving wallet_type where present) and flip the
     * journal to Posted.
     */
    protected function finalizePost(JournalEntry $entry, array $lines, string $referenceType, string $referenceId): void
    {
        $centsLines = array_map(function (array $line) {
            $mapped = [
                'account_code' => $line['account_code'],
                'debit' => (int) round(($line['debit'] ?? 0) * 100),
                'credit' => (int) round(($line['credit'] ?? 0) * 100),
            ];

            if (! empty($line['wallet_type'])) {
                $mapped['wallet_type'] = $line['wallet_type'];
            }

            return $mapped;
        }, $lines);

        $transactionId = $this->financialService->recordTransaction(
            $entry->company_id ?: 1,
            $referenceType,
            $referenceId,
            $centsLines,
            $entry->description ?? ''
        );

        $entry->update([
            'status' => 'Posted',
            'posted_by' => auth()->id(),
            'ledger_transaction_id' => $transactionId,
        ]);
    }
}
