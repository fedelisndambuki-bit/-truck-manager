<?php

namespace App\Filament\Resources\DriverPodResource\Pages;

use App\Filament\Resources\DriverPodResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListDriverPods extends ListRecords
{
    protected static string $resource = DriverPodResource::class;

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-clipboard-document-check',
            'title' => 'Proof of Delivery',
            'subtitle' => 'Review proof-of-delivery submissions from drivers.',
        ]);
    }
}
