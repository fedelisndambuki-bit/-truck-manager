<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class CrmSales extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'CRM & Sales';

    protected static ?string $navigationGroup = 'CRM & Sales';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'crm-sales';
}
