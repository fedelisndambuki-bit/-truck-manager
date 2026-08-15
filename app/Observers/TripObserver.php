<?php

namespace App\Observers;

use App\Helpers\AppSettings;
use App\Models\Trip;
use App\Enums\TripStatus;
use App\Enums\TruckStatus;
use App\Models\Invoice;
use Illuminate\Support\Str;

class TripObserver
{
    public function saving(Trip $trip): void
    {
        // Auto-fill route name if empty
        if (empty($trip->route_name) && !empty($trip->origin) && !empty($trip->destination)) {
            $trip->route_name = "{$trip->origin} -> {$trip->destination}";
        }

        // Auto-fill cargo details from cargo_id if they are empty
        if ($trip->cargo_id) {
            $cargo = $trip->cargo;
            if ($cargo) {
                if (empty($trip->cargo_type)) {
                    $trip->cargo_type = $cargo->cargo_type;
                }
                if (empty($trip->cargo_weight) || $trip->cargo_weight == 0) {
                    $trip->cargo_weight = $cargo->weight;
                }
            }
        }

        // Auto-fill client details from client_id if they are empty
        if ($trip->client_id) {
            $client = $trip->client;
            if ($client) {
                if (empty($trip->client_name)) {
                    $trip->client_name = $client->company_name;
                }
                if (empty($trip->client_contact)) {
                    $trip->client_contact = $client->contact_person;
                }
            }
        }
    }

    public function created(Trip $trip): void
    {
        $this->updateTruckStatus($trip);
    }

    public function updated(Trip $trip): void
    {
        $this->updateTruckStatus($trip);
        $this->updateTruckMileage($trip);
        $this->autoGenerateInvoice($trip);
    }

    protected function autoGenerateInvoice(Trip $trip): void
    {
        if ($trip->status === TripStatus::Completed) {
            $this->creditDriverWallet($trip);
            
            // Check if invoice already exists for this trip
            $exists = Invoice::where('trip_id', $trip->id)->exists();
            if ($exists) return;

            $subtotal = $trip->revenue ?? 0;
            $tax = $subtotal * AppSettings::taxRate();
            $total = $subtotal + $tax;

            Invoice::create([
                'company_id' => $trip->company_id,
                'trip_id' => $trip->id,
                'invoice_number' => 'INV-' . strtoupper(Str::random(6)),
                'client_id' => $trip->client_id,
                'truck_id' => $trip->truck_id,
                'driver_id' => $trip->employee_id,
                'route_from' => $trip->origin,
                'route_to' => $trip->destination,
                'invoice_date' => now(),
                'due_date' => now()->addDays(14), // Default 14 days due
                'line_items' => [
                    [
                        'description' => 'Freight charges for trip: ' . ($trip->route_name ?? $trip->origin . ' to ' . $trip->destination),
                        'quantity' => 1,
                        'rate' => $subtotal,
                    ]
                ],
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total_amount' => $total,
                'status' => 'draft',
            ]);
        }
    }

    protected function creditDriverWallet(Trip $trip): void
    {
        if (!$trip->employee_id || !$trip->revenue) return;

        // Idempotent per trip — without this, saving any field on an
        // already-Completed trip (unrelated edits, repeated form saves)
        // re-runs this and stacks a fresh Commission Expense every time.
        // Mirrors the invoice dedupe check just below in autoGenerateInvoice().
        if (\App\Models\Expense::where('trip_id', $trip->id)->where('category', 'Commission')->exists()) {
            return;
        }

        $employee = $trip->driver; // Employee model
        if (!$employee) return;

        $driver = $employee->driver; // real FK bridge to the mobile Driver model
        if (!$driver) return;

        $commissionRate = $driver->commission_rate ?? 10.00;
        $commissionAmount = $trip->revenue * ($commissionRate / 100);

        if ($commissionAmount <= 0) return;

        try {
            // Create an Expense record. 
            // The ExpenseObserver will handle:
            // 1. Updating Employee financials (allowances)
            // 2. Recording Ledger Entry (Expense vs Liability)
            // 3. Updating Driver Wallet (since payment_method is 'Wallet')
            \App\Models\Expense::create([
                'company_id'           => $trip->company_id,
                'employee_id'          => $employee->id,
                'truck_id'             => $trip->truck_id,
                'trip_id'              => $trip->id,
                'category'             => 'Commission',
                'amount'               => $commissionAmount,
                'date'                 => now(),
                'payment_method'       => 'Wallet',
                'payslip_earning_type' => 'commission_bonus',
                'description'          => "Commission for Trip #{$trip->id} ({$trip->origin} -> {$trip->destination})",
                // Trip completion is itself the approval event — no separate
                // manual review step for per-trip driver commissions.
                'status'               => 'Paid',
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error processing trip commission: " . $e->getMessage());
        }
    }

    protected function updateTruckStatus(Trip $trip): void
    {
        $truck = $trip->truck;
        if (!$truck) return;

        if ($trip->status === TripStatus::InProgress) {
            $truck->status = TruckStatus::InTransit;
            $truck->save();
        } elseif ($trip->status === TripStatus::Completed) {
            if ($truck->status === TruckStatus::InTransit) {
                $truck->status = TruckStatus::Available;
                $truck->save();
            }
        }
    }

    protected function updateTruckMileage(Trip $trip): void
    {
        // Only update mileage when trip is completed and has distance
        if ($trip->status === TripStatus::Completed && $trip->distance_km > 0) {
            $truck = $trip->truck;
            if ($truck) {
                // Ideally, we'd add distance_km to the truck's mileage.
                // However, without tracking *which* trip added mileage, repeatedly saving might double-count.
                // A better approach is to rely on FuelLog or Maintenance for precise odometer readings,
                // or just increment if we assume this observer runs once per state change.
                // For simplicity/safety, we won't auto-increment here to avoid infinite loops or double counting
                // unless we tracked 'mileage_processed' flag on the trip.
                // Leaving this empty for now or we could implement a more complex logic later.
            }
        }
    }
}
