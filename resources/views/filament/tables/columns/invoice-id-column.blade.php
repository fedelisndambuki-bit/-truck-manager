<div class="filament-table-invoice-id">
    <i class="fas fa-file-invoice-dollar"></i>
    <a href="{{ \App\Filament\Resources\InvoiceResource::getUrl('view', ['record' => $getRecord()->id]) }}">
        {{ $getRecord()->invoice_number }}
    </a>
</div>