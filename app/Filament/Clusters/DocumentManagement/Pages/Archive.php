<?php

namespace App\Filament\Clusters\DocumentManagement\Pages;

use App\Filament\Clusters\DocumentManagement;
use App\Models\EdmsDocument;
use App\Traits\HasModuleAccess;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

class Archive extends Page
{
    use HasModuleAccess;
    use WithPagination;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'documents.archive';
    }

    protected static ?string $cluster = DocumentManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Archive';

    protected static ?int $navigationSort = 20;

    protected static ?string $title = 'Archive';

    protected static string $view = 'filament.clusters.document-management.pages.archive';

    public string $search = '';

    public function getRecords()
    {
        $companyId = Auth::user()?->company_id;

        return EdmsDocument::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Archived')
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->latest('updated_at')
            ->paginate(15);
    }

    public function restore(int $id): void
    {
        $document = EdmsDocument::findOrFail($id);
        $document->update(['status' => 'Approved']);

        Notification::make()->title('Document restored from archive')->success()->send();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }
}
