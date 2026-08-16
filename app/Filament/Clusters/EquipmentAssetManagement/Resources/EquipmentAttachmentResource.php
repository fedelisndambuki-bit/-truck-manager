<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources;

use App\Filament\Clusters\EquipmentAssetManagement;
use App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentAttachmentResource\Pages;
use App\Models\Equipment;
use App\Models\EquipmentAttachment;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EquipmentAttachmentResource extends Resource
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

    protected static ?string $model = EquipmentAttachment::class;

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $navigationLabel = 'Attachments';

    protected static ?int $navigationSort = 17;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['currentEquipment']);
    }

    public static function getTypeOptions(): array
    {
        return array_combine(EquipmentAttachment::TYPES, EquipmentAttachment::TYPES);
    }

    public static function getStatusOptions(): array
    {
        return array_combine(EquipmentAttachment::STATUSES, EquipmentAttachment::STATUSES);
    }

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Available' => 'success', 'In Use' => 'info', 'Under Maintenance' => 'warning', 'Out of Service' => 'danger', default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Attachment')->schema([
                    Forms\Components\TextInput::make('attachment_code')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\Select::make('attachment_type')->options(self::getTypeOptions())->required(),
                    Forms\Components\TextInput::make('serial_number'),
                    Forms\Components\TagsInput::make('compatible_equipment_types')->placeholder('e.g. Excavator, Backhoe'),
                    Forms\Components\Select::make('status')->options(self::getStatusOptions())->default('Available')->required(),
                ])->columns(3),

                Forms\Components\Section::make('Current Assignment')->schema([
                    Forms\Components\Select::make('current_equipment_id')
                        ->label('Currently Attached To')
                        ->relationship('currentEquipment', 'name')
                        ->getOptionLabelFromRecordUsing(fn (Equipment $record) => "{$record->name} ({$record->equipment_code})")
                        ->searchable()->preload(),
                    Forms\Components\TextInput::make('total_usage_hours')->numeric()->suffix('hrs'),
                ])->columns(2),

                Forms\Components\Section::make('Procurement')->schema([
                    Forms\Components\DatePicker::make('purchase_date'),
                    Forms\Components\TextInput::make('purchase_cost')->numeric()->prefix(session('currency', 'KES')),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('attachment_code')->label('Code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('attachment_type')->label('Type')->badge(),
                Tables\Columns\TextColumn::make('currentEquipment.name')->label('Attached To')->placeholder('Unassigned'),
                Tables\Columns\TextColumn::make('total_usage_hours')->suffix(' hrs'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('attachment_type')->label('Type')->options(self::getTypeOptions()),
                Tables\Filters\SelectFilter::make('status')->options(self::getStatusOptions()),
            ])
            ->actions([
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
            'index' => Pages\ListEquipmentAttachments::route('/'),
            'create' => Pages\CreateEquipmentAttachment::route('/create'),
            'edit' => Pages\EditEquipmentAttachment::route('/{record}/edit'),
        ];
    }
}
