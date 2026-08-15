<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TruckStatus: string implements HasLabel
{
    case Available = 'Available';
    case InTransit = 'In Transit';
    case UnderMaintenance = 'Under Maintenance';

    public function getLabel(): ?string
    {
        return $this->value;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Available => 'success',
            self::InTransit => 'info',
            self::UnderMaintenance => 'danger',
        };
    }
}
