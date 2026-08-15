<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    <style>
        .page-header-custom{background-color:#2f4f4f;padding:25px 35px;color:#fff;border-radius:10px;margin-bottom:20px}
        .page-header-content{display:flex;align-items:center;gap:12px}
        .page-header-icon{background:rgba(255,255,255,.15);padding:10px;border-radius:8px;display:flex;align-items:center;justify-content:center}
        .page-header-custom h1{font-size:24px;font-weight:600;margin-bottom:4px}
        .page-header-custom p{font-size:13px;opacity:.85;font-weight:400}
        
        /* Pagination custom styles */
        .fi-ta-pagination button, 
        .fi-ta-pagination a {
            background-color: darkslategrey !important;
            color: white !important;
        }
        .fi-ta-pagination button:hover, 
        .fi-ta-pagination a:hover {
            background-color: #264040 !important; /* Slightly darker slategrey */
        }
        .fi-ta-pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>

    <div class="page-header-custom">
        <div class="page-header-content">
            <div class="page-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-4-4h-1m-4 6H2v-2a4 4 0 014-4h1m4-6a4 4 0 110-8 4 4 0 010 8z" />
                </svg>
            </div>
            <div>
                <h1>User Management Dashboard</h1>
                <p>Overview of application users, status and activity</p>
            </div>
        </div>
    </div>

    @if (method_exists($this, 'getCustomHeaderWidgets'))
        <x-filament-widgets::widgets :widgets="$this->getCustomHeaderWidgets()" :columns="$this->getColumns()" />
    @endif

    {{ $this->form ?? '' }}

    @if (method_exists($this, 'getCustomFooterWidgets'))
        <div class="mt-8">
            <x-filament-widgets::widgets :widgets="$this->getCustomFooterWidgets()" :columns="$this->getColumns()" />
        </div>
    @endif
</x-filament-panels::page>
