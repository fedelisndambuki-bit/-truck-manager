<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources;

use App\Filament\Clusters\EquipmentAssetManagement;
use App\Filament\Clusters\EquipmentAssetManagement\Resources\ServiceHistoryResource\Pages;
use App\Models\Maintenance;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Read-mostly view of the existing Maintenance table (extended in this
 * phase with technician/downtime/recommendations/warranty/work-order link)
 * — Service History is this table, not a new one; every closed
 * MaintenanceWorkOrder writes/updates a row here automatically.
 */
class ServiceHistoryResource extends Resource
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

    protected static ?string $model = Maintenance::class;

    protected static ?string $slug = 'service-history';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Service History';

    protected static ?int $navigationSort = 8;

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfolistSection::make('Service Record')->schema([
                TextEntry::make('equipment.name')->label('Equipment'),
                TextEntry::make('maintenance_type')->label('Type')->badge(),
                TextEntry::make('maintenance_date')->date(),
                TextEntry::make('technician.full_name')->label('Technician')->placeholder('—'),
                TextEntry::make('workOrder.work_order_number')->label('Work Order')->placeholder('—'),
            ])->columns(3),

            InfolistSection::make('Cost & Downtime')->schema([
                TextEntry::make('cost')->money(session('currency', 'KES'))->placeholder('—'),
                TextEntry::make('downtime_hours')->suffix(' hrs')->placeholder('—'),
                TextEntry::make('warranty_covered')->label('Warranty Covered')->badge()->formatStateUsing(fn (bool $state) => $state ? 'Yes' : 'No')->color(fn (bool $state) => $state ? 'success' : 'gray'),
            ])->columns(3),

            InfolistSection::make('Details')->schema([
                TextEntry::make('description')->columnSpanFull()->placeholder('—'),
                TextEntry::make('recommendations')->columnSpanFull()->placeholder('—'),
            ]),
        ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Follow-up')->schema([
                    Forms\Components\Textarea::make('recommendations')->columnSpanFull(),
                    Forms\Components\Toggle::make('warranty_covered')->label('Covered Under Warranty'),
                    Forms\Components\FileUpload::make('photos')->image()->multiple()->disk('public')->directory('service-history')->columnSpanFull(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->whereNotNull('equipment_id'))
            ->columns([
                Tables\Columns\TextColumn::make('equipment.name')->label('Equipment')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('maintenance_type')->label('Type')->badge(),
                Tables\Columns\TextColumn::make('maintenance_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('technician.full_name')->label('Technician')->placeholder('—'),
                Tables\Columns\TextColumn::make('cost')->money(session('currency', 'KES'))->placeholder('—'),
                Tables\Columns\TextColumn::make('downtime_hours')->suffix(' hrs')->placeholder('—'),
                Tables\Columns\IconColumn::make('warranty_covered')->label('Warranty')->boolean(),
                Tables\Columns\TextColumn::make('workOrder.work_order_number')->label('Work Order')->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('equipment_id')->label('Equipment')->relationship('equipment', 'name'),
                Tables\Filters\TernaryFilter::make('warranty_covered')->label('Warranty'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()->label('Add Follow-up'),
            ])
            ->defaultSort('maintenance_date', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceHistory::route('/'),
            'edit' => Pages\EditServiceHistory::route('/{record}/edit'),
        ];
    }
}
