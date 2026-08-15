@php
    $methods = [
        'document_register' => 'generateDocumentRegister',
        'drawing_register' => 'generateDrawingRegister',
        'rfi_report' => 'generateRfiReport',
        'approval_report' => 'generateApprovalReport',
        'revision_report' => 'generateRevisionReport',
        'audit_trail_report' => 'generateAuditTrailReport',
        'expired_documents' => 'generateExpiredDocuments',
        'contract_register' => 'generateContractRegister',
        'document_activity' => 'generateDocumentActivity',
    ];
@endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" />',
        'title' => 'Reports',
        'subtitle' => 'Generate document register, drawing register, RFI, approval and audit reports as PDF',
    ])

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($this->getReportDefinitions() as $report)
            <x-filament::section>
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                        <x-filament::icon :icon="$report['icon']" class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $report['label'] }}</div>
                    </div>
                </div>
                <div class="mt-4">
                    <button
                        wire:click="{{ $methods[$report['key']] }}"
                        class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg h-9 pl-3 pr-4 text-sm inline-grid gap-1 shadow-sm bg-primary-600 text-white hover:bg-primary-500"
                    >
                        <x-filament::icon icon="heroicon-o-document-arrow-down" class="h-4 w-4" />
                        Export PDF
                    </button>
                </div>
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>
