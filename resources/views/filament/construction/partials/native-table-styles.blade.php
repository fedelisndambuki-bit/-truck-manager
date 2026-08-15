{{-- Restyles Filament's native table (used where enterprise table features like
     column-toggle/bulk-actions/sorting are required) to match the Tanaki Hub
     table look (teal section title bar, light header row, uppercase column
     labels, subtle hover) used throughout the rest of the app's hand-rolled
     tables. Pair with the .table-section / .table-header-custom wrapper from
     filament.construction.partials.styles so the teal title bar sits flush
     against this card, exactly like every other resource's list page. --}}
<style>
    .tanaki-native-table .fi-ta-ctn {
        border-radius: 0 0 10px 10px !important;
        border: 1px solid #e5e7eb !important;
        border-top: none !important;
        box-shadow: none !important;
        overflow: hidden;
    }
    .tanaki-native-table .fi-ta-header-cell {
        background-color: #f9fafb !important;
        border-bottom: 2px solid #e5e7eb !important;
    }
    .tanaki-native-table .fi-ta-header-cell-label {
        font-size: 11px !important;
        font-weight: 700 !important;
        color: #374151 !important;
        text-transform: uppercase;
        letter-spacing: .05em;
    }
    .tanaki-native-table .fi-ta-row {
        border-bottom: 1px solid #f3f4f6 !important;
    }
    .tanaki-native-table .fi-ta-row:hover {
        background-color: #f9fafb !important;
    }
    .tanaki-native-table .fi-ta-cell,
    .tanaki-native-table .fi-ta-text-item-label {
        font-size: 13px !important;
        color: #374151 !important;
    }
    .tanaki-native-table .fi-ta-table {
        background-color: #fff !important;
    }
    /* Toolbar (search / filters / column toggle / header actions) sits above
       the teal title bar, styled plainly so it doesn't compete with it. */
    .tanaki-native-table .fi-ta-header {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
</style>
