<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EquipmentResource\Pages;
use App\Models\Equipment;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EquipmentResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction', 'quarry'];
    }

    protected static ?string $model = Equipment::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationGroup = 'Construction';

    protected static ?int $navigationSort = 4;

    public static function getCategoryOptions(): array
    {
        return [
            'Excavator' => 'Excavator',
            'Bulldozer' => 'Bulldozer',
            'Crane' => 'Crane',
            'Loader' => 'Loader',
            'Grader' => 'Grader',
            'Other' => 'Other',
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            'Available' => 'Available',
            'In Use' => 'In Use',
            'Under Maintenance' => 'Under Maintenance',
            'Out of Service' => 'Out of Service',
            'Reserved' => 'Reserved',
        ];
    }

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Available' => 'success',
            'In Use' => 'info',
            'Under Maintenance' => 'warning',
            'Out of Service' => 'danger',
            'Reserved' => 'gray',
            default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Equipment Details')->schema([
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\TextInput::make('equipment_code')->label('Equipment Code')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\Select::make('equipment_type')->label('Category')->options(self::getCategoryOptions())->required(),
                    Forms\Components\TextInput::make('registration_number'),
                    Forms\Components\Select::make('status')->options(self::getStatusOptions())->default('Available')->required(),
                    Forms\Components\TextInput::make('serial_number'),
                    Forms\Components\DatePicker::make('purchase_date'),
                    Forms\Components\FileUpload::make('photo')->image()->disk('public')->directory('equipment-photos'),
                ])->columns(2),

                Forms\Components\Section::make('Financial & Compliance')->schema([
                    Forms\Components\TextInput::make('purchase_cost')->numeric()->prefix(session('currency', 'KES')),
                    Forms\Components\TextInput::make('current_value')->numeric()->prefix(session('currency', 'KES')),
                    Forms\Components\TextInput::make('insurance_provider'),
                    Forms\Components\DatePicker::make('insurance_expiry'),
                    Forms\Components\DatePicker::make('warranty_expiry'),
                ])->columns(2),

                Forms\Components\Textarea::make('notes')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('equipment_code')->label('Code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('equipment_type')->label('Category')->badge(),
                Tables\Columns\TextColumn::make('registration_number')->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => self::getStatusColor($state)),
                Tables\Columns\TextColumn::make('serial_number')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('purchase_date')->date()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('insurance_expiry')->date()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(self::getStatusOptions()),
                Tables\Filters\SelectFilter::make('equipment_type')->label('Category')->options(self::getCategoryOptions()),
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
            'view' => Pages\ViewEquipment::route('/{record}'),
            'edit' => Pages\EditEquipment::route('/{record}/edit'),
        ];
    }
}
