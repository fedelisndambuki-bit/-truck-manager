<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class Certificate extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'issued_at' => 'datetime',
    ];

    public const TYPES = [
        'Certificate of Analysis', 'Certificate of Compliance', 'Quality Certificate',
        'Test Certificate', 'Customer Certificate',
    ];

    public const STATUSES = ['Draft', 'Issued', 'Revoked'];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (Certificate $certificate) {
            if (empty($certificate->certificate_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $certificate->certificate_number = 'CERT-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }

            if (empty($certificate->status)) {
                $certificate->status = 'Draft';
            }
        });

        static::created(function (Certificate $certificate) {
            if (empty($certificate->qr_code)) {
                try {
                    $url = route('filament.admin.laboratory-quality-control.resources.certificates.view', ['record' => $certificate->id]);
                } catch (\Throwable $e) {
                    $url = $certificate->certificate_number;
                }

                $certificate->qr_code = QrCode::size(200)->generate($url);
                $certificate->saveQuietly();
            }
        });
    }

    public function sample(): BelongsTo
    {
        return $this->belongsTo(LabSample::class, 'lab_sample_id');
    }

    public function dispatchOrder(): BelongsTo
    {
        return $this->belongsTo(DispatchOrder::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'customer_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'issued_by_id');
    }

    public function issue(int $issuedById): void
    {
        $this->update(['status' => 'Issued', 'issued_by_id' => $issuedById, 'issued_at' => now()]);
    }

    public function revoke(): void
    {
        $this->update(['status' => 'Revoked']);
    }
}
