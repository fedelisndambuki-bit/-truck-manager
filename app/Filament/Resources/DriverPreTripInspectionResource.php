<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DriverPreTripInspectionResource\Pages;
use App\Models\DriverPreTripInspection;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

use App\Traits\HasModuleAccess;

class DriverPreTripInspectionResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'maintenance';
    }

    public static function getRequiredIndustries(): array
    {
        return ['trucking'];
    }
    protected static ?string $model = DriverPreTripInspection::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Driver Submissions';

    protected static ?string $navigationLabel = 'Pre-Trip Inspections';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('submitted_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('driver.user.name')->label('Driver')->searchable(),
                Tables\Columns\TextColumn::make('trip.id')->label('Trip')->sortable(),
                Tables\Columns\IconColumn::make('brakes')->boolean(),
                Tables\Columns\IconColumn::make('lights')->boolean(),
                Tables\Columns\IconColumn::make('tires')->boolean(),
                Tables\Columns\IconColumn::make('load_secured')->label('Load Secured')->boolean(),
                Tables\Columns\TextColumn::make('notes')->limit(40)->toggleable(),
                Tables\Columns\TextColumn::make('submitted_at')->dateTime()->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDriverPreTripInspections::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user) {
            $query->whereHas('driver', fn (Builder $q) => $q->where('company_id', $user->company_id));
        }

        return $query;
    }
}
