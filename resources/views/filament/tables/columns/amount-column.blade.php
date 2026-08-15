<span class="filament-table-amount {{ $getRecord()->total_amount >= 0 ? 'positive' : 'negative' }}">
    ${{ number_format($getRecord()->total_amount, 2) }}
</span>