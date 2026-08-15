<?php

namespace App\Models;

use App\Traits\HasApprovalWorkflow;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class DispatchOrder extends BaseModel
{
    use HasApprovalWorkflow;

    protected $guarded = [];

    /**
     * Plain (non-persisted) re-entrancy guard for the `updated()` hook below.
     * Eloquent doesn't sync $original until after the outer save() finishes,
     * so wasChanged('status') still reports true inside a nested update()
     * triggered from within the same event — this flag is the only reliable
     * way to guarantee approveDispatch() runs exactly once per transition.
     */
    protected bool $approvalInProgress = false;

    protected $casts = [
        'security_checklist' => 'array',
        'axle_weights' => 'array',
        'vehicle_inspection_passed' => 'boolean',
        'tare_locked' => 'boolean',
        'driver_license_expiry' => 'date',
        'gate_in_at' => 'datetime',
        'tare_weighed_at' => 'datetime',
        'loading_instruction_generated_at' => 'datetime',
        'loading_started_at' => 'datetime',
        'loading_completed_at' => 'datetime',
        'gross_weighed_at' => 'datetime',
        'exit_at' => 'datetime',
        'security_clearance_at' => 'datetime',
        'eta' => 'datetime',
        'geofence_entered_at' => 'datetime',
        'geofence_exited_at' => 'datetime',
        'arrived_at' => 'datetime',
    ];

    public const STATUSES = [
        'Draft', 'Planned',
        'Checked In', 'Tare Weighed', 'Loading Instruction Generated', 'Loading',
        'Loading Completed', 'Awaiting Gross Weight', 'Gross Weighed', 'Pending Approval',
        'Approved', 'Rejected', 'Exited', 'En Route', 'Delivered', 'Delayed', 'Cancelled',
    ];

    public const PRIORITIES = ['Low', 'Normal', 'High', 'Urgent'];

    public const WEIGHT_STATUSES = ['Within Tolerance', 'Underweight', 'Overweight', 'Critical'];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (DispatchOrder $order) {
            if (empty($order->dispatch_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $order->dispatch_number = 'DSP-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }

            if (empty($order->tolerance_tonnes)) {
                $order->tolerance_tonnes = 0.20;
            }

            if (empty($order->priority)) {
                $order->priority = 'Normal';
            }

            if ($order->reweigh_count === null) {
                $order->reweigh_count = 0;
            }
        });

        static::updated(function (DispatchOrder $order) {
            if ($order->wasChanged('status') && $order->status === 'Approved') {
                $order->approveDispatch();
            }
        });
    }

    // ── Relations ────────────────────────────────────────────────────────────

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'customer_id');
    }

    public function customerOrder(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class);
    }

    public function transportCompany(): BelongsTo
    {
        return $this->belongsTo(TransportCompany::class);
    }

    public function weighbridge(): BelongsTo
    {
        return $this->belongsTo(Weighbridge::class);
    }

    public function loadingBay(): BelongsTo
    {
        return $this->belongsTo(LoadingBay::class);
    }

    public function loaderEquipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'loader_equipment_id');
    }

    public function loaderOperator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'loader_operator_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function stockpile(): BelongsTo
    {
        return $this->belongsTo(Stockpile::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(StockReservation::class, 'reservation_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function securityOfficer(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'security_officer_id');
    }

    public function tareOperator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'tare_operator_id');
    }

    public function grossOperator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'gross_operator_id');
    }

    public function securityClearanceBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'security_clearance_by_id');
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'generated_by_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(DispatchOrderLine::class);
    }

    public function loadingInstructions(): HasMany
    {
        return $this->hasMany(LoadingInstruction::class);
    }

    public function currentLoadingInstruction(): ?LoadingInstruction
    {
        return $this->loadingInstructions()->latest('id')->first();
    }

    public function weighingLogs(): HasMany
    {
        return $this->hasMany(WeighbridgeWeighingLog::class);
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(WeighbridgeException::class);
    }

    public function proofOfDelivery(): HasOne
    {
        return $this->hasOne(ProofOfDelivery::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(WeighbridgeDocument::class, 'documentable');
    }

    // ── Workflow methods ─────────────────────────────────────────────────────

    /**
     * Record the initial (empty) tare weight, lock it, and immediately
     * generate the Smart Loading Instruction — "no manual intervention
     * should be required" per spec.
     */
    public function recordTare(array $data): void
    {
        $tareWeight = (float) $data['tare_weight'];

        $this->update([
            'tare_weight' => $tareWeight,
            'weighbridge_id' => $data['weighbridge_id'] ?? $this->weighbridge_id,
            'tare_operator_id' => $data['tare_operator_id'] ?? null,
            'tare_weighed_at' => $data['recorded_at'] ?? now(),
            'tare_locked' => true,
            'required_gross_weight' => $tareWeight + (float) $this->target_net_weight,
            'max_allowed_weight' => $tareWeight + (float) $this->target_net_weight + (float) $this->tolerance_tonnes,
            'min_allowed_weight' => $tareWeight + (float) $this->target_net_weight - (float) $this->tolerance_tonnes,
            'status' => 'Tare Weighed',
        ]);

        WeighbridgeWeighingLog::create([
            'company_id' => $this->company_id,
            'dispatch_order_id' => $this->id,
            'sequence' => 1,
            'weighing_type' => 'Tare',
            'weight' => $tareWeight,
            'weighbridge_id' => $this->weighbridge_id,
            'operator_id' => $this->tare_operator_id,
            'recorded_at' => $this->tare_weighed_at,
        ]);

        $this->generateLoadingInstruction();
    }

    public function generateLoadingInstruction(): LoadingInstruction
    {
        $instruction = LoadingInstruction::create([
            'company_id' => $this->company_id,
            'dispatch_order_id' => $this->id,
            'loading_bay_id' => $this->loading_bay_id,
            'loader_equipment_id' => $this->loader_equipment_id,
            'loader_operator_id' => $this->loader_operator_id,
            'stockpile_id' => $this->stockpile_id,
            'target_net_weight' => $this->target_net_weight,
            'required_gross_weight' => $this->required_gross_weight,
            'priority' => $this->priority,
            'status' => 'Waiting',
            'generated_at' => now(),
        ]);

        $this->update([
            'loading_instruction_generated_at' => now(),
            'status' => 'Loading Instruction Generated',
        ]);

        return $instruction;
    }

    /**
     * Record a gross weighing event (initial or a reload). Never overwrites
     * a previous weighing — each attempt is its own WeighbridgeWeighingLog row.
     */
    public function recordGross(array $data): void
    {
        $grossWeight = (float) $data['gross_weight'];
        $sequence = $this->weighingLogs()->where('weighing_type', 'Gross')->count() + 1;
        $previousLog = $this->weighingLogs()->where('weighing_type', 'Gross')->latest('sequence')->first();

        $netWeight = $grossWeight - (float) $this->tare_weight;
        $variance = $netWeight - (float) $this->target_net_weight;
        $variancePercent = (float) $this->target_net_weight > 0
            ? round(($variance / (float) $this->target_net_weight) * 100, 2)
            : 0.0;

        $tolerance = (float) $this->tolerance_tonnes;
        $weightStatus = match (true) {
            abs($variance) <= $tolerance => 'Within Tolerance',
            $variance < -$tolerance => 'Underweight',
            $variance > $tolerance * 2 => 'Critical',
            default => 'Overweight',
        };

        $this->update([
            'gross_weight' => $grossWeight,
            'net_weight' => $netWeight,
            'variance' => $variance,
            'variance_percent' => $variancePercent,
            'weight_status' => $weightStatus,
            'weighbridge_id' => $data['weighbridge_id'] ?? $this->weighbridge_id,
            'gross_operator_id' => $data['gross_operator_id'] ?? null,
            'gross_weighed_at' => $data['recorded_at'] ?? now(),
            'weather' => $data['weather'] ?? $this->weather,
            'status' => 'Gross Weighed',
            'reweigh_count' => $sequence > 1 ? $this->reweigh_count + 1 : $this->reweigh_count,
        ]);

        WeighbridgeWeighingLog::create([
            'company_id' => $this->company_id,
            'dispatch_order_id' => $this->id,
            'sequence' => $sequence,
            'weighing_type' => 'Gross',
            'weight' => $grossWeight,
            'weighbridge_id' => $this->weighbridge_id,
            'operator_id' => $this->gross_operator_id,
            'recorded_at' => $this->gross_weighed_at,
            'reason' => $sequence > 1 ? ($data['reason'] ?? 'Reload') : null,
            'comments' => $data['comments'] ?? null,
            'previous_weight' => $previousLog?->weight,
        ]);
    }

    /**
     * The Automatic Decision Engine: branches on the real weight_status
     * (and a real customer credit-hold check) computed in recordGross().
     */
    public function runDecisionEngine(): void
    {
        if ($this->customer && $this->customer->credit_status === 'Hold') {
            $this->update(['status' => 'Pending Approval']);
            $this->submitForApproval('dispatch-weight-approval', [
                'title' => 'Dispatch Order — ' . $this->dispatch_number . ' (Customer Credit Hold)',
                'metadata' => ['dispatch_order_id' => $this->id, 'critical' => true, 'reason' => 'Customer Credit Hold'],
            ]);

            return;
        }

        if ($this->hasQualityHold()) {
            $this->update(['status' => 'Pending Approval']);
            $this->submitForApproval('dispatch-quality-hold-approval', [
                'title' => 'Dispatch Order — ' . $this->dispatch_number . ' (Quality Hold)',
                'metadata' => ['dispatch_order_id' => $this->id, 'critical' => true, 'reason' => 'Quality Hold'],
            ]);

            return;
        }

        match ($this->weight_status) {
            'Within Tolerance' => $this->update(['status' => 'Approved']),
            'Underweight' => tap($this)->update(['status' => 'Loading'])->generateLoadingInstruction(),
            'Overweight' => $this->escalateForApproval(false),
            'Critical' => $this->escalateForApproval(true),
            default => null,
        };
    }

    protected function escalateForApproval(bool $critical): void
    {
        $this->update(['status' => 'Pending Approval']);
        $this->submitForApproval('dispatch-weight-approval', [
            'title' => 'Dispatch Order — ' . $this->dispatch_number . ($critical ? ' (Critical Variance)' : ''),
            'metadata' => ['dispatch_order_id' => $this->id, 'critical' => $critical],
        ]);
    }

    /**
     * Within-tolerance auto-approval OR the real consequence of a manual
     * HasApprovalWorkflow approval — reduces stock, logs the movement, and
     * creates a real Invoice. No manual stock/finance adjustments.
     */
    public function approveDispatch(): void
    {
        if ($this->approvalInProgress) {
            return;
        }

        $this->approvalInProgress = true;

        if ($this->lines()->exists()) {
            foreach ($this->lines as $line) {
                $line->stockpile?->decrement('quantity_available', $line->quantity);

                StockMovement::record([
                    'company_id' => $this->company_id,
                    'movement_type' => 'Dispatch',
                    'material_id' => $line->material_id,
                    'quantity' => $line->quantity,
                    'source_stockpile_id' => $line->stockpile_id,
                    'vehicle_registration' => $this->vehicle_registration,
                ], $this);
            }
        } else {
            $this->stockpile?->decrement('quantity_available', (float) $this->net_weight);

            StockMovement::record([
                'company_id' => $this->company_id,
                'movement_type' => 'Dispatch',
                'material_id' => $this->material_id,
                'quantity' => $this->net_weight,
                'source_stockpile_id' => $this->stockpile_id,
                'operator_id' => $this->gross_operator_id,
                'vehicle_registration' => $this->vehicle_registration,
            ], $this);
        }

        $invoice = $this->customer_id ? $this->createInvoice() : null;

        $this->update(['status' => 'Approved', 'invoice_id' => $invoice?->id]);

        $this->approvalInProgress = false;
    }

    protected function createInvoice(): Invoice
    {
        $rate = (float) ($this->material?->selling_price ?? $this->stockpile?->unit_value ?? 0);
        $subtotal = $rate * (float) $this->net_weight;
        $tax = $subtotal * 0.16;

        if ($this->lines()->exists()) {
            $lineItems = $this->lines->map(fn (DispatchOrderLine $line) => [
                'description' => $line->material?->name ?? 'Product',
                'quantity' => $line->quantity,
                'rate' => $line->unit_price ?? 0,
            ])->all();
            $subtotal = collect($lineItems)->sum(fn ($l) => $l['quantity'] * $l['rate']);
            $tax = $subtotal * 0.16;
        } else {
            $lineItems = [[
                'description' => ($this->material?->name ?? 'Product') . ' — Dispatch ' . $this->dispatch_number,
                'quantity' => $this->net_weight,
                'rate' => $rate,
            ]];
        }

        return Invoice::create([
            'company_id' => $this->company_id,
            'invoice_number' => 'INV-' . strtoupper(Str::random(6)),
            'client_id' => $this->customer_id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(14),
            'line_items' => $lineItems,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total_amount' => $subtotal + $tax,
            'status' => 'draft',
        ]);
    }

    /**
     * Real pre-exit checklist — computed from actual recorded state, not new
     * boolean columns duplicating what's already tracked.
     */
    public function canExit(): bool
    {
        return $this->status === 'Approved'
            && (bool) $this->vehicle_inspection_passed
            && $this->tare_locked
            && ! $this->exceptions()->whereIn('status', ['Draft', 'Submitted'])->exists()
            && ! $this->hasQualityHold();
    }

    /**
     * Real check against the Laboratory & Quality Control module's
     * QualityHold ledger — any Active hold on this order's own stockpile or
     * any line's stockpile blocks dispatch. Toggleable via LabSetting so a
     * company can decouple dispatch from lab holds without a code change.
     * Defense-in-depth: checked both at auto-approval time
     * (runDecisionEngine) and again at the exit barrier (canExit).
     */
    public function hasQualityHold(): bool
    {
        if (! \App\Models\LabSetting::getSetting($this->company_id, 'enforce_dispatch_quality_gate', true)) {
            return false;
        }

        $stockpileIds = collect([$this->stockpile_id])
            ->merge($this->lines->pluck('stockpile_id'))
            ->filter()
            ->unique();

        if ($stockpileIds->isEmpty()) {
            return false;
        }

        return \App\Models\QualityHold::whereIn('stockpile_id', $stockpileIds)->where('status', 'Active')->exists();
    }

    public function recordExit(array $data): void
    {
        $this->update([
            'exit_at' => $data['exit_at'] ?? now(),
            'exit_gate' => $data['exit_gate'] ?? null,
            'security_clearance_by_id' => $data['security_clearance_by_id'] ?? null,
            'security_clearance_at' => now(),
            'delivery_note_path' => $data['delivery_note_path'] ?? null,
            'status' => 'Exited',
        ]);
    }

    /**
     * Manually-updated in-transit position — schema-ready for a future real
     * GPS/telematics integration, not a live device feed today.
     */
    public function updateLiveLocation(array $data): void
    {
        $this->update([
            'current_gps_lat' => $data['current_gps_lat'] ?? $this->current_gps_lat,
            'current_gps_lng' => $data['current_gps_lng'] ?? $this->current_gps_lng,
            'distance_remaining_km' => $data['distance_remaining_km'] ?? $this->distance_remaining_km,
            'eta' => $data['eta'] ?? $this->eta,
            'status' => in_array($this->status, ['Exited', 'En Route']) ? 'En Route' : $this->status,
        ]);
    }

    public function recordArrival(array $data): void
    {
        $this->update([
            'arrived_at' => $data['arrived_at'] ?? now(),
            'geofence_entered_at' => $data['geofence_entered_at'] ?? now(),
            'delivery_notes' => $data['delivery_notes'] ?? $this->delivery_notes,
            'status' => 'Delivered',
        ]);
    }

    public function markDelayed(?string $reason = null): void
    {
        $this->update([
            'status' => 'Delayed',
            'delivery_notes' => $reason ?? $this->delivery_notes,
        ]);
    }

    /**
     * Real fitness check before allowing a truck/driver assignment to be
     * saved — delegates to DispatchSchedulingService so the same logic is
     * shared by the Scheduling Resource's validation and any other caller.
     */
    public function canSchedule(): bool
    {
        if (! $this->vehicle_registration) {
            return true;
        }

        $truck = \App\Models\Truck::where('plate_number', $this->vehicle_registration)->first();

        if (! $truck) {
            return true;
        }

        return app(\App\Services\Quarry\DispatchSchedulingService::class)->truckPassesFitness($truck, $this->company_id);
    }

    public function turnaroundMinutes(): ?int
    {
        if (! $this->gate_in_at || ! $this->exit_at) {
            return null;
        }

        return $this->gate_in_at->diffInMinutes($this->exit_at);
    }

    public function loadingDurationMinutes(): ?int
    {
        if (! $this->loading_started_at || ! $this->loading_completed_at) {
            return null;
        }

        return $this->loading_started_at->diffInMinutes($this->loading_completed_at);
    }

    public function waitingMinutes(): ?int
    {
        if (! $this->gate_in_at) {
            return null;
        }

        $end = $this->tare_weighed_at ?? now();

        return $this->gate_in_at->diffInMinutes($end);
    }
}
