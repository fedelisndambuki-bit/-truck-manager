<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Finance extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Accounting';

    protected static ?string $navigationGroup = 'Accounting';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'finance';
}
