<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\FeatureFlagResource\Pages;
use App\Models\FeatureFlag;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FeatureFlagResource extends Resource
{
    protected static ?string $model = FeatureFlag::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationGroup = 'Platform';

    protected static ?string $navigationLabel = 'Feature Flags';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('company_id')
                ->label('Company (blank = not company-specific)')
                ->relationship(
                    name: 'company',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn ($query) => $query->whereNotNull('name'),
                )
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->name ?? ("Company #{$record->id}"))
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('plan_id')
                ->label('Plan (blank = not plan-specific)')
                ->relationship(
                    name: 'plan',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn ($query) => $query->whereNotNull('name'),
                )
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->name ?? ("Plan #{$record->id}"))
                ->searchable()
                ->preload(),
            Forms\Components\TextInput::make('key')->required(),
            Forms\Components\Toggle::make('enabled')->required(),
            Forms\Components\TextInput::make('rollout_percentage')
                ->label('Rollout %')
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->default(100)
                ->required(),
            Forms\Components\KeyValue::make('config'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('company.name')->label('Company')->placeholder('Global'),
            Tables\Columns\TextColumn::make('plan.name')->label('Plan')->placeholder('Any Plan'),
            Tables\Columns\TextColumn::make('key')->searchable(),
            Tables\Columns\IconColumn::make('enabled')->boolean(),
            Tables\Columns\TextColumn::make('rollout_percentage')->label('Rollout %'),
            Tables\Columns\TextColumn::make('updated_at')->since(),
        ])->actions([
            Tables\Actions\EditAction::make(),
        ])->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFeatureFlags::route('/'),
            'create' => Pages\CreateFeatureFlag::route('/create'),
            'edit' => Pages\EditFeatureFlag::route('/{record}/edit'),
        ];
    }
}
