<?php

namespace App\Modules\Settlement;

use App\Modules\Payments\Transaction;
use App\Modules\Ledger\LedgerService;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class SettlementService
{
    private $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function processDailySettlement()
    {
        // Process per company to maintain tenancy
        $companiesWithPending = Transaction::where('status', 'completed')
            ->where('settled', false)
            ->distinct()
            ->pluck('company_id');

        foreach ($companiesWithPending as $companyId) {
            $this->settleForCompany($companyId);
        }
    }

    private function settleForCompany($companyId)
    {
        $pendingTransactions = Transaction::where('company_id', $companyId)
            ->where('status', 'completed')
            ->where('settled', false)
            ->get();

        if ($pendingTransactions->isEmpty()) return;

        DB::transaction(function () use ($pendingTransactions, $companyId) {
            $settlement = Settlement::create([
                'company_id' => $companyId,
                'settlement_id' => 'SET-' . Str::upper(Str::random(8)),
                'period_start' => Carbon::yesterday()->startOfDay(),
                'period_end' => Carbon::yesterday()->endOfDay(),
                'status' => 'completed',
                'completed_at' => now()
            ]);

            $grossTotal = 0;
            $feesTotal = 0;
            $netTotal = 0;

            foreach ($pendingTransactions as $txn) {
                $gatewayFee = $txn->amount * 0.029;
                $platformFee = $txn->amount * 0.01;
                $totalTxnFee = $gatewayFee + $platformFee;
                $netTxn = $txn->amount - $totalTxnFee;

                SettlementItem::create([
                    'settlement_id' => $settlement->id,
                    'transaction_id' => $txn->id,
                    'gross_amount' => $txn->amount,
                    'fees' => $totalTxnFee,
                    'net_amount' => $netTxn
                ]);

                $txn->update(['settled' => true]);

                $this->ledgerService->recordEntry($companyId, $txn->transaction_id, 'customer_liability', 'gateway_fees', $gatewayFee, 'Gateway Fee');
                $this->ledgerService->recordEntry($companyId, $txn->transaction_id, 'customer_liability', 'platform_revenue', $platformFee, 'Platform Revenue');

                $grossTotal += $txn->amount;
                $feesTotal += $totalTxnFee;
                $netTotal += $netTxn;
            }

            $settlement->update([
                'gross_amount' => $grossTotal,
                'fees_total' => $feesTotal,
                'net_amount' => $netTotal,
            ]);
        });
    }
}
