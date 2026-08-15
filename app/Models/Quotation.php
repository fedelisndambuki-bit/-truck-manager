<?php

namespace App\Models;

use App\Models\CustomerOrder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

class Quotation extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'validity_date' => 'date',
        'freight' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (Quotation $quotation) {
            if (empty($quotation->quotation_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $quotation->quotation_number = 'QT-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function parentQuotation(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_quotation_id');
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'prepared_by_id');
    }

    public function isExpired(): bool
    {
        return (bool) $this->validity_date && $this->validity_date->isPast();
    }

    /**
     * Recomputes the cached header totals from the current line items.
     * Called from QuotationItem's saved/deleted hooks so the header is
     * always in sync without the caller having to remember to do it.
     */
    public function recalculateTotals(): void
    {
        $items = $this->items()->get();

        $subtotal = $items->sum(fn (QuotationItem $i) => (float) $i->quantity * (float) $i->unit_price);
        $discountTotal = $items->sum(fn (QuotationItem $i) => (float) $i->quantity * (float) $i->unit_price * ((float) $i->discount_percent / 100));
        $afterDiscount = $subtotal - $discountTotal;
        $taxTotal = $items->sum(function (QuotationItem $i) {
            $lineAfterDiscount = (float) $i->quantity * (float) $i->unit_price * (1 - (float) $i->discount_percent / 100);

            return $lineAfterDiscount * ((float) $i->tax_percent / 100);
        });

        $this->subtotal = round($subtotal, 2);
        $this->discount_total = round($discountTotal, 2);
        $this->tax_total = round($taxTotal, 2);
        $this->total = round($afterDiscount + $taxTotal + (float) $this->freight, 2);
        $this->saveQuietly();
    }

    /**
     * Accepts the quotation: creates one CustomerOrder per line (matching
     * CustomerOrder's existing single-material-per-order schema) and flips
     * the quotation to Accepted. Reuses CustomerOrder as-is — its own
     * reserveStock()/createDispatchOrder() are untouched by this.
     *
     * @return CustomerOrder[]
     *
     * @throws RuntimeException if the quotation isn't in an acceptable
     *                           status, or accepting would exceed the
     *                           client's credit limit.
     */
    public function accept(): array
    {
        if (! in_array($this->status, ['Submitted', 'Approved'], true)) {
            throw new RuntimeException('Only a Submitted or Approved quotation can be accepted.');
        }

        $client = $this->client;

        if ($client && (float) $client->credit_limit > 0) {
            $projectedBalance = $client->outstandingBalance() + (float) $this->total;

            if ($projectedBalance > (float) $client->credit_limit) {
                $recipients = app(\App\Services\CrmNotificationRecipients::class)->forCompany($this->company_id);
                if ($recipients->isNotEmpty()) {
                    \Illuminate\Support\Facades\Notification::send($recipients, new \App\Notifications\CrmNotification(
                        'credit_limit_exceeded',
                        ['reference' => $this->quotation_number]
                    ));
                }

                throw new RuntimeException(
                    "Accepting this quotation would exceed {$client->company_name}'s credit limit ".
                    "(outstanding ".number_format($client->outstandingBalance(), 2).' + this quotation '.
                    number_format((float) $this->total, 2).' > limit '.number_format((float) $client->credit_limit, 2).').'
                );
            }
        }

        $orders = $this->items()->get()->map(function (QuotationItem $item) {
            return CustomerOrder::create([
                'company_id' => $this->company_id,
                'customer_id' => $this->client_id,
                'material_id' => $item->material_id,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'quotation_number' => $this->quotation_number,
                'status' => 'Draft',
            ]);
        })->all();

        $this->update(['status' => 'Accepted']);

        return $orders;
    }
}
