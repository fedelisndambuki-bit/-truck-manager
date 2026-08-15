<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CompanyIndustry: string implements HasLabel
{
    case Trucking = 'trucking';
    case Construction = 'construction';
    case Quarry = 'quarry';
    case MultiOperation = 'multi_operation';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Trucking => 'Trucking & Fleet',
            self::Construction => 'Construction',
            self::Quarry => 'Quarry',
            self::MultiOperation => 'Multi-Operation',
        };
    }
}
