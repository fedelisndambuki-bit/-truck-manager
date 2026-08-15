<?php

namespace App\Filament\Clusters\DocumentManagement\Pages;

use App\Filament\Clusters\DocumentManagement;
use App\Models\DigitalSignature;
use App\Models\EdmsDocument;
use App\Models\Drawing;
use App\Traits\HasModuleAccess;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class DigitalSignatures extends Page
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'documents.view';
    }

    protected static ?string $cluster = DocumentManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-finger-print';

    protected static ?string $navigationLabel = 'Digital Signatures';

    protected static ?int $navigationSort = 18;

    protected static ?string $title = 'Digital Signatures';

    protected static string $view = 'filament.clusters.document-management.pages.digital-signatures';

    /** @var array<int, string> */
    public array $signerNames = [];

    public function getRows(): array
    {
        $companyId = Auth::user()?->company_id;

        return DigitalSignature::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->with(['signable', 'signer'])
            ->latest('signed_at')
            ->limit(100)
            ->get()
            ->map(fn (DigitalSignature $signature) => [
                'signature' => $signature,
                'entity_label' => class_basename($signature->signable_type),
                'entity_title' => $signature->signable?->title ?? $signature->signable?->subject ?? '—',
            ])
            ->all();
    }

    /**
     * Approved documents/drawings that don't yet carry a signature — the
     * "awaiting signature" queue shown alongside the signature register.
     */
    public function getUnsignedApproved(): array
    {
        $companyId = Auth::user()?->company_id;

        $documents = EdmsDocument::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Approved')
            ->whereDoesntHave('signatures')
            ->get()
            ->map(fn ($d) => ['type' => EdmsDocument::class, 'label' => 'Document', 'id' => $d->id, 'title' => $d->title]);

        $drawings = Drawing::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Approved')
            ->whereDoesntHave('signatures')
            ->get()
            ->map(fn ($d) => ['type' => Drawing::class, 'label' => 'Drawing', 'id' => $d->id, 'title' => $d->title]);

        return $documents->concat($drawings)->all();
    }

    public function sign(string $type, int $id): void
    {
        $signerName = trim($this->signerNames[$id] ?? '');

        if ($signerName === '') {
            Notification::make()->title('Type your name to sign')->warning()->send();

            return;
        }

        $entity = $type::findOrFail($id);

        DigitalSignature::create([
            'company_id' => $entity->company_id,
            'signable_type' => $type,
            'signable_id' => $id,
            'signer_name' => $signerName,
            'signature_data' => $signerName,
            'ip_address' => request()?->ip(),
        ]);

        unset($this->signerNames[$id]);

        Notification::make()->title('Signature recorded for ' . $entity->title)->success()->send();
    }
}
