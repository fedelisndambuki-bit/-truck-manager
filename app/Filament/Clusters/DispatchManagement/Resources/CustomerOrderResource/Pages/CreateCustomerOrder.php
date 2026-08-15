<?php

namespace App\Filament\Clusters\DispatchManagement\Resources\CustomerOrderResource\Pages;

use App\Filament\Clusters\DispatchManagement\Resources\CustomerOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerOrder extends CreateRecord
{
    protected static string $resource = CustomerOrderResource::class;
}
