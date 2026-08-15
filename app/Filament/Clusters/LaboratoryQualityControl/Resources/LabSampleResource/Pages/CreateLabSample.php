<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources\LabSampleResource\Pages;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\LabSampleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLabSample extends CreateRecord
{
    protected static string $resource = LabSampleResource::class;

    public function mount(): void
    {
        parent::mount();

        if ($requestId = request()->query('sampling_request_id')) {
            $this->data['sampling_request_id'] = $requestId;
        }
    }
}
