<x-filament-widgets::widget>
    <style>
        /* Target the table header specifically for this resource */
        .fi-ta-header-cell {
            background-color: #2f4f4f !important;
            color: white !important;
        }
        
        .fi-ta-header-cell * {
            color: white !important;
        }

        /* Ensure sorting icons are white */
        .fi-ta-header-cell-label svg {
            color: white !important;
        }
        
        /* Hide this widget's container */
        .fi-wi-stats-overview-stat {
            display: none !important;
        }
    </style>
</x-filament-widgets::widget>
