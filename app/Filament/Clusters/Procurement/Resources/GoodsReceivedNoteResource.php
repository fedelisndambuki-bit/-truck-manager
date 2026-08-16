<?php

namespace App\Filament\Clusters\Procurement\Resources;

use App\Filament\Clusters\Procurement;
use App\Filament\Clusters\Procurement\Resources\GoodsReceivedNoteResource\Pages;
use App\Models\GoodsReceivedNote;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class GoodsReceivedNoteResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = Procurement::class;

    public static function getModuleSlug(): ?string
    {
        return 'procurement.view';
    }

    protected static ?string $model = GoodsReceivedNote::class;

    protected static ?string $slug = 'goods-receiving';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Goods Receiving';

    protected static ?int $navigationSort = 10;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['materialDelivery.material']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Goods Received Note')->schema([
                    Forms\Components\Select::make('material_delivery_id')
                        ->label('Delivery')
                        ->relationship('materialDelivery', 'delivery_number')
                        ->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('received_quantity')->numeric()->required(),
                    Forms\Components\TextInput::make('accepted_quantity')->numeric()->required(),
                    Forms\Components\TextInput::make('rejected_quantity')->numeric()->default(0),
                    Forms\Components\TextInput::make('damaged_quantity')->numeric()->default(0),
                    Forms\Components\Select::make('inspected_by_id')->label('Inspector')->relationship('inspectedBy', 'full_name')->searchable()->preload(),
                    Forms\Components\TextInput::make('delivery_note_reference'),
                    Forms\Components\TextInput::make('invoice_reference'),
                ])->columns(3),

                Forms\Components\Section::make('Quality Inspection')
                    ->description('Only quality-approved goods are credited to inventory when this GRN is approved.')
                    ->schema([
                        Forms\Components\KeyValue::make('checklist')
                            ->keyLabel('Check')
                            ->valueLabel('Result')
                            ->addActionLabel('Add Check')
                            ->default([
                                'Visual Inspection' => 'Pass',
                                'Specification Verification' => 'Pass',
                                'Quantity Verification' => 'Pass',
                                'Packaging Inspection' => 'Pass',
                            ])
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('photos')->image()->multiple()->disk('public')->directory('grn-photos')->columnSpanFull(),
                        Forms\Components\Toggle::make('quality_approved'),
                        Forms\Components\Textarea::make('inspection_notes')->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('grn_number')->label('GRN #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('materialDelivery.delivery_number')->label('Delivery'),
                Tables\Columns\TextColumn::make('materialDelivery.material.name')->label('Material'),
                Tables\Columns\TextColumn::make('received_quantity')->label('Received')->numeric(2),
                Tables\Columns\TextColumn::make('accepted_quantity')->label('Accepted')->numeric(2)->color('success'),
                Tables\Columns\TextColumn::make('rejected_quantity')->label('Rejected')->numeric(2)->color(fn ($state) => $state > 0 ? 'danger' : null),
                Tables\Columns\TextColumn::make('damaged_quantity')->label('Damaged')->numeric(2)->color(fn ($state) => $state > 0 ? 'warning' : null),
                Tables\Columns\IconColumn::make('quality_approved')->boolean(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => $state === 'Approved' ? 'success' : 'warning'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(['Pending' => 'Pending', 'Approved' => 'Approved']),
                Tables\Filters\TernaryFilter::make('quality_approved'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('This credits the delivery\'s warehouse stock with the accepted quantity.')
                    ->action(function (GoodsReceivedNote $record) {
                        try {
                            $record->approve();
                            Notification::make()->title('GRN approved and stock credited')->success()->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()->title('Cannot approve')->body($e->getMessage())->danger()->send();
                        }
                    })
                    ->visible(fn (GoodsReceivedNote $record) => $record->status === 'Pending' && $record->quality_approved
                        && (Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('procurement.manage'))),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGoodsReceivedNotes::route('/'),
            'create' => Pages\CreateGoodsReceivedNote::route('/create'),
            'edit' => Pages\EditGoodsReceivedNote::route('/{record}/edit'),
        ];
    }
}
