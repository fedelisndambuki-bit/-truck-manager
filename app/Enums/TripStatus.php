<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TripStatus: string implements HasLabel
{
    case Scheduled = 'Scheduled';
    case InProgress = 'In Progress';
    case Completed = 'Completed';
    case Cancelled = 'Cancelled';

    public function getLabel(): ?string
    {
        return $this->value;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Scheduled => 'gray',
            self::InProgress => 'warning',
            self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }
}
