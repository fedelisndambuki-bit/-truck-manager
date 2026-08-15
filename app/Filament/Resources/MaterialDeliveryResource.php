<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaterialDeliveryResource\Pages;
use App\Models\MaterialDelivery;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MaterialDeliveryResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'materials.view';
    }

    protected static ?string $model = MaterialDelivery::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Deliveries';

    protected static ?string $navigationGroup = 'Materials Management';

    protected static ?int $navigationSort = 10;

    public static function getInspectionStatusColor(?string $status): string
    {
        return match ($status) {
            'Passed' => 'success',
            'Failed' => 'danger',
            default => 'warning',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Delivery Details')->schema([
                    Forms\Components\Select::make('material_id')
                        ->label('Material')
                        ->relationship('material', 'name')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('supplier_id')
                        ->label('Supplier')
                        ->relationship('supplier', 'company_name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('purchase_order_id')
                        ->label('Purchase Order')
                        ->relationship('purchaseOrder', 'po_number')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('warehouse_id')
                        ->label('Warehouse')
                        ->relationship('warehouse', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('project_id')
                        ->label('Project')
                        ->relationship('project', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('site_id')
                        ->label('Site')
                        ->relationship('site', 'name')
                        ->searchable()->preload(),
                    Forms\Components\TextInput::make('supplier_name')->label('Supplier Name (free text)')->helperText('Used when no formal Supplier record is selected above.'),
                    Forms\Components\TextInput::make('vehicle'),
                    Forms\Components\TextInput::make('driver_name'),
                    Forms\Components\Select::make('received_by_id')
                        ->label('Received By')
                        ->relationship('receivedBy', 'full_name')
                        ->searchable()->preload(),
                    Forms\Components\TextInput::make('quantity')->numeric()->required(),
                    Forms\Components\DatePicker::make('delivered_at')->default(now()),
                    Forms\Components\DateTimePicker::make('arrival_time'),
                    Forms\Components\DateTimePicker::make('offloading_time'),
                    Forms\Components\TextInput::make('purchase_order_ref')->label('PO Reference (free text)'),
                    Forms\Components\Select::make('inspection_status')->options([
                        'Pending' => 'Pending', 'Passed' => 'Passed', 'Failed' => 'Failed',
                    ])->default('Pending'),
                    Forms\Components\Select::make('status')->options([
                        'Pending' => 'Pending', 'Delivered' => 'Delivered',
                    ])->default('Pending')->required(),
                    Forms\Components\FileUpload::make('photos')
                        ->image()
                        ->multiple()
                        ->disk('public')
                        ->directory('delivery-photos')
                        ->columnSpanFull(),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('delivery_number')->label('Delivery #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('material.name')->label('Material')->searchable(),
                Tables\Columns\TextColumn::make('supplier.company_name')->label('Supplier')->placeholder(fn (MaterialDelivery $record) => $record->supplier_name ?? '—'),
                Tables\Columns\TextColumn::make('warehouse.name')->label('Warehouse')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('quantity')->numeric(2),
                Tables\Columns\TextColumn::make('delivered_at')->date()->sortable(),
                Tables\Columns\TextColumn::make('inspection_status')->badge()->color(fn (?string $state) => self::getInspectionStatusColor($state)),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => $state === 'Delivered' ? 'success' : 'warning'),
                Tables\Columns\IconColumn::make('grn_logged')->label('GRN Logged')->boolean()
                    ->getStateUsing(fn (MaterialDelivery $record) => (bool) $record->goodsReceivedNote),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('inspection_status')->options(['Pending' => 'Pending', 'Passed' => 'Passed', 'Failed' => 'Failed']),
                Tables\Filters\SelectFilter::make('status')->options(['Pending' => 'Pending', 'Delivered' => 'Delivered']),
                Tables\Filters\SelectFilter::make('warehouse_id')->label('Warehouse')->relationship('warehouse', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('delivered_at', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMaterialDeliveries::route('/'),
            'create' => Pages\CreateMaterialDelivery::route('/create'),
            'edit' => Pages\EditMaterialDelivery::route('/{record}/edit'),
        ];
    }
}
