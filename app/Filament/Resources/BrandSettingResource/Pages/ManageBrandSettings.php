<?php

namespace App\Filament\Resources\BrandSettingResource\Pages;

use App\Filament\Resources\BrandSettingResource;
use App\Models\BrandSetting;
use Filament\Resources\Pages\ManageRecords;

class ManageBrandSettings extends ManageRecords
{
    protected static string $resource = BrandSettingResource::class;

    public function mount(): void
    {
        $user = auth()->user();
        $query = BrandSetting::query();
        if ($user && $user->company_id) {
            $query->where('company_id', $user->company_id);
        }

        $record = $query->first();

        if (! $record) {
            $record = new BrandSetting();
            $record->fill([
                'company_name' => config('app.name'),
                'logo_path' => null,
                'icon_path' => null,
            ]);
            if ($user && $user->company_id) {
                $record->company_id = $user->company_id;
            }
            $record->save();
        }

        $this->redirect(BrandSettingResource::getUrl('edit', ['record' => $record->getKey()]));
    }
}

