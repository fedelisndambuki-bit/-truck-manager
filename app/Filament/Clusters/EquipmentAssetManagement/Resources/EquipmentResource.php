<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources;

use App\Filament\Clusters\EquipmentAssetManagement;
use App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentResource\Pages;
use App\Models\Equipment;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EquipmentResource extends Resource
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

    protected static ?string $model = Equipment::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Equipment Register';

    protected static ?int $navigationSort = 1;

    public static function getStatusOptions(): array
    {
        return array_combine(Equipment::STATUSES, Equipment::STATUSES);
    }

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Operational', 'Available' => 'success',
            'Idle' => 'gray',
            'Under Maintenance', 'In Use' => 'warning',
            'Out of Service' => 'danger',
            'Disposed' => 'gray',
            default => 'gray',
        };
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfolistSection::make('Identification')->schema([
                TextEntry::make('equipment_code')->label('Equipment Number'),
                TextEntry::make('asset_number')->placeholder('—'),
                TextEntry::make('name'),
                TextEntry::make('equipment_type')->label('Type'),
                TextEntry::make('category.name')->label('Category')->placeholder('—'),
                TextEntry::make('manufacturer')->placeholder('—'),
                TextEntry::make('brand')->placeholder('—'),
                TextEntry::make('model')->placeholder('—'),
                TextEntry::make('year')->placeholder('—'),
                TextEntry::make('serial_number')->placeholder('—'),
                TextEntry::make('engine_number')->placeholder('—'),
                TextEntry::make('vin')->label('VIN')->placeholder('—'),
                TextEntry::make('registration_number')->placeholder('—'),
            ])->columns(4),

            InfolistSection::make('Organization & Location')->schema([
                TextEntry::make('department.name')->label('Department')->placeholder('—'),
                TextEntry::make('extractionSite.name')->label('Quarry / Site')->placeholder('—'),
                TextEntry::make('store.name')->label('Home Store / Depot')->placeholder('—'),
                TextEntry::make('current_location')->placeholder('—'),
            ])->columns(4),

            InfolistSection::make('Procurement & Financial')->schema([
                TextEntry::make('purchase_date')->date()->placeholder('—'),
                TextEntry::make('commission_date')->date()->placeholder('—'),
                TextEntry::make('supplier')->placeholder('—'),
                TextEntry::make('purchase_cost')->money(session('currency', 'KES'))->placeholder('—'),
                TextEntry::make('replacement_cost')->money(session('currency', 'KES'))->placeholder('—'),
                TextEntry::make('current_value')->label('Current Book Value')->money(session('currency', 'KES'))->placeholder('—'),
                TextEntry::make('useful_life_years')->label('Useful Life (Years)')->placeholder('—'),
                TextEntry::make('residual_value')->money(session('currency', 'KES'))->placeholder('—'),
            ])->columns(4),

            InfolistSection::make('Status & Compliance')->schema([
                TextEntry::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
                TextEntry::make('insurance_expiry')->date()->placeholder('—'),
                TextEntry::make('warranty_expiry')->date()->placeholder('—'),
                TextEntry::make('next_calibration_date')->date()->placeholder('—'),
                TextEntry::make('engine_hours')->suffix(' hrs')->placeholder('—'),
                TextEntry::make('fuel_level')->suffix('%')->placeholder('—'),
            ])->columns(4),
        ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identification')->schema([
                    Forms\Components\TextInput::make('equipment_code')->label('Equipment Number')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\TextInput::make('asset_number')->label('Asset Number'),
                    Forms\Components\TextInput::make('name')->label('Equipment Name')->required(),
                    Forms\Components\TextInput::make('equipment_type')->label('Equipment Type')->required(),
                    Forms\Components\Select::make('equipment_category_id')->label('Category')->relationship('category', 'name')->searchable()->preload(),
                    Forms\Components\TextInput::make('manufacturer'),
                    Forms\Components\TextInput::make('brand'),
                    Forms\Components\TextInput::make('model'),
                    Forms\Components\TextInput::make('year')->numeric()->minValue(1950)->maxValue((int) date('Y') + 1),
                    Forms\Components\TextInput::make('serial_number'),
                    Forms\Components\TextInput::make('engine_number'),
                    Forms\Components\TextInput::make('vin')->label('VIN'),
                    Forms\Components\TextInput::make('registration_number'),
                ])->columns(3),

                Forms\Components\Section::make('Organization & Location')->schema([
                    Forms\Components\Select::make('department_id')->label('Department')->relationship('department', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('extraction_site_id')->label('Quarry / Site')->relationship('extractionSite', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('eam_store_id')->label('Home Store / Depot')->relationship('store', 'name')->searchable()->preload(),
                    Forms\Components\TextInput::make('current_location'),
                    Forms\Components\TextInput::make('gps_lat')->label('GPS Latitude')->numeric(),
                    Forms\Components\TextInput::make('gps_lng')->label('GPS Longitude')->numeric(),
                ])->columns(3),

                Forms\Components\Section::make('Procurement & Commissioning')->schema([
                    Forms\Components\DatePicker::make('purchase_date'),
                    Forms\Components\DatePicker::make('commission_date'),
                    Forms\Components\TextInput::make('supplier'),
                    Forms\Components\TextInput::make('purchase_cost')->numeric()->prefix(session('currency', 'KES')),
                    Forms\Components\TextInput::make('replacement_cost')->numeric()->prefix(session('currency', 'KES')),
                    Forms\Components\TextInput::make('current_value')->label('Current Book Value')->numeric()->prefix(session('currency', 'KES')),
                    Forms\Components\TextInput::make('useful_life_years')->label('Useful Life (Years)')->numeric(),
                    Forms\Components\TextInput::make('residual_value')->numeric()->prefix(session('currency', 'KES')),
                ])->columns(3),

                Forms\Components\Section::make('Status & Compliance')->schema([
                    Forms\Components\Select::make('status')->options(self::getStatusOptions())->default('Operational')->required(),
                    Forms\Components\TextInput::make('insurance_provider'),
                    Forms\Components\DatePicker::make('insurance_expiry'),
                    Forms\Components\DatePicker::make('warranty_expiry'),
                    Forms\Components\DatePicker::make('next_calibration_date'),
                    Forms\Components\FileUpload::make('photo')->image()->disk('public')->directory('equipment-photos'),
                ])->columns(3),

                Forms\Components\Textarea::make('notes')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('equipment_code')->label('Equip. No.')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('asset_number')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category.name')->label('Category')->badge()->placeholder('—'),
                Tables\Columns\TextColumn::make('manufacturer')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('model')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('extractionSite.name')->label('Quarry')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
                Tables\Columns\TextColumn::make('engine_hours')->numeric()->suffix(' hrs')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('current_value')->label('Book Value')->money(session('currency', 'KES'))->placeholder('—')->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(self::getStatusOptions()),
                Tables\Filters\SelectFilter::make('equipment_category_id')->label('Category')->relationship('category', 'name'),
                Tables\Filters\SelectFilter::make('extraction_site_id')->label('Quarry')->relationship('extractionSite', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEquipment::route('/'),
            'create' => Pages\CreateEquipment::route('/create'),
            'edit' => Pages\EditEquipment::route('/{record}/edit'),
        ];
    }
}
