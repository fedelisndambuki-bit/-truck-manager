<x-filament-widgets::widget>
    <x-filament::section class="page-header-custom" style="background-color: #2f4f4f !important;"> <!-- Force darkslate color in all modes -->
        <div class="page-header-content">
            <div class="page-header-icon">
                <x-filament::icon icon="heroicon-o-document-text" class="h-5 w-5" />
            </div>
            <div>
                <h1>Invoices</h1>
                <p>Manage and track all your client invoices</p>
            </div>
        </div>
    </x-filament::section>

    <style>
        /* General styling for header consistent with settlements & payslips */
        .page-header-custom {
            background: #2f4f4f; /* Darkslategray to match other modules (light & dark mode) */
            padding: 25px 35px;
            color: white;
            border-radius: 10px; /* Match Filament section styling */
            margin-bottom: 20px;
        }

        .page-header-content {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-header-icon {
            background: rgba(255, 255, 255, 0.15);
            padding: 10px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .page-header-custom h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 4px;
            color: white; /* Ensure title is white */
        }

        .page-header-custom p {
            font-size: 13px;
            opacity: 0.85;
            font-weight: 400;
            color: white; /* Ensure description is white */
        }
    </style>
</x-filament-widgets::widget>