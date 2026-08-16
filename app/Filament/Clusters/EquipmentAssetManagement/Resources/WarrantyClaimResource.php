<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources;

use App\Filament\Clusters\EquipmentAssetManagement;
use App\Filament\Clusters\EquipmentAssetManagement\Resources\WarrantyClaimResource\Pages;
use App\Models\Equipment;
use App\Models\WarrantyClaim;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WarrantyClaimResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = EquipmentAssetManagement::class;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'eam.view';
    }

    protected static ?string $model = WarrantyClaim::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Warranty';

    protected static ?int $navigationSort = 22;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['equipment']);
    }

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Filed' => 'gray', 'Approved' => 'info', 'Rejected' => 'danger', 'Repaired' => 'warning', 'Closed' => 'success', default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Claim')->schema([
                    Forms\Components\TextInput::make('claim_number')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\Select::make('equipment_id')
                        ->relationship('equipment', 'name')
                        ->getOptionLabelFromRecordUsing(fn (Equipment $record) => "{$record->name} ({$record->equipment_code})")
                        ->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('covered_component'),
                    Forms\Components\DatePicker::make('filed_at')->default(now())->required(),
                    Forms\Components\Select::make('status')->options(array_combine(WarrantyClaim::STATUSES, WarrantyClaim::STATUSES))->default('Filed')->required(),
                    Forms\Components\TextInput::make('supplier_contact'),
                    Forms\Components\TextInput::make('cost_recovered')->numeric()->prefix(session('currency', 'KES')),
                ])->columns(3),

                Forms\Components\Textarea::make('issue_description')->columnSpanFull(),
                Forms\Components\Textarea::make('resolution')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('claim_number')->label('Claim #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('equipment.name')->label('Equipment')->searchable(),
                Tables\Columns\TextColumn::make('covered_component')->placeholder('—'),
                Tables\Columns\TextColumn::make('filed_at')->date()->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
                Tables\Columns\TextColumn::make('cost_recovered')->money(session('currency', 'KES'))->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(array_combine(WarrantyClaim::STATUSES, WarrantyClaim::STATUSES)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('filed_at', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWarrantyClaims::route('/'),
            'create' => Pages\CreateWarrantyClaim::route('/create'),
            'edit' => Pages\EditWarrantyClaim::route('/{record}/edit'),
        ];
    }
}
