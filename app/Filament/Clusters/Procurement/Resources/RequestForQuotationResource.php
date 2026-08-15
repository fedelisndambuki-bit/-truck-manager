<?php

namespace App\Filament\Clusters\Procurement\Resources;

use App\Filament\Clusters\Procurement;
use App\Filament\Clusters\Procurement\Resources\RequestForQuotationResource\Pages;
use App\Models\RequestForQuotation;
use App\Models\Supplier;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RequestForQuotationResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = Procurement::class;

    public static function getModuleSlug(): ?string
    {
        return 'procurement.view';
    }

    protected static ?string $model = RequestForQuotation::class;

    protected static ?string $slug = 'rfqs';

    protected static ?string $navigationIcon = 'heroicon-o-envelope-open';

    protected static ?string $navigationLabel = 'RFQ Management';

    protected static ?int $navigationSort = 3;

    public static function getStatusOptions(): array
    {
        return array_combine(RequestForQuotation::STATUSES, RequestForQuotation::STATUSES);
    }

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Draft' => 'gray', 'Open' => 'info', 'Closed' => 'warning', 'Awarded' => 'success', 'Cancelled' => 'danger', default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('RFQ')->schema([
                    Forms\Components\TextInput::make('rfq_number')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\TextInput::make('title'),
                    Forms\Components\Select::make('purchase_request_id')->label('Originating Requisition')->relationship('purchaseRequest', 'pr_number')->searchable()->preload(),
                    Forms\Components\DatePicker::make('closing_date')->required(),
                    Forms\Components\DatePicker::make('delivery_date'),
                    Forms\Components\Select::make('status')->options(self::getStatusOptions())->default('Draft')->required(),
                ])->columns(3),

                Forms\Components\Section::make('Terms & Evaluation')->schema([
                    Forms\Components\TagsInput::make('required_documents')->placeholder('Add required document'),
                    Forms\Components\Textarea::make('terms')->columnSpanFull(),
                    Forms\Components\Textarea::make('conditions')->columnSpanFull(),
                    Forms\Components\KeyValue::make('evaluation_criteria')->keyLabel('Criterion')->valueLabel('Weight / Note')->columnSpanFull(),
                ]),

                Forms\Components\Section::make('Items')->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship('items')
                        ->schema([
                            Forms\Components\Select::make('material_id')->label('Material')->relationship('material', 'name')->searchable()->preload()->columnSpan(2),
                            Forms\Components\TextInput::make('description')->columnSpan(2),
                            Forms\Components\TextInput::make('quantity')->numeric()->required(),
                            Forms\Components\TextInput::make('unit'),
                            Forms\Components\Textarea::make('specifications')->columnSpanFull(),
                        ])->columns(6)->addActionLabel('Add Item')->columnSpanFull(),
                ]),

                Forms\Components\Section::make('Invite Suppliers')->schema([
                    Forms\Components\Select::make('invitedSuppliersPicker')
                        ->label('Suppliers')
                        ->multiple()
                        ->options(fn () => Supplier::query()->where('status', 'Active')->pluck('company_name', 'id'))
                        ->searchable()
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Forms\Components\Select $component, $state, $record) {
                            if ($record) {
                                $component->state($record->invitedSuppliers()->pluck('suppliers.id')->all());
                            }
                        })
                        ->columnSpanFull(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('rfq_number')->label('RFQ #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('title')->placeholder('—'),
                Tables\Columns\TextColumn::make('closing_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('items_count')->label('Items')->counts('items'),
                Tables\Columns\TextColumn::make('quotations_count')->label('Quotations')->counts('quotations'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(self::getStatusOptions()),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Open for Bidding')
                    ->icon('heroicon-o-lock-open')
                    ->color('info')
                    ->visible(fn (RequestForQuotation $record) => $record->status === 'Draft')
                    ->action(function (RequestForQuotation $record) {
                        $record->update(['status' => 'Open']);
                        foreach ($record->invitedSuppliers as $supplier) {
                            $record->rfqSuppliers()->updateOrCreate(
                                ['supplier_id' => $supplier->id],
                                ['company_id' => $record->company_id, 'invited_at' => now(), 'status' => 'Invited']
                            );
                        }
                        Notification::make()->title('RFQ opened for bidding')->success()->send();
                    }),
                Tables\Actions\Action::make('close')
                    ->label('Close')
                    ->icon('heroicon-o-lock-closed')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (RequestForQuotation $record) => $record->status === 'Open')
                    ->action(fn (RequestForQuotation $record) => $record->update(['status' => 'Closed'])),
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
            'index' => Pages\ListRequestForQuotations::route('/'),
            'create' => Pages\CreateRequestForQuotation::route('/create'),
            'edit' => Pages\EditRequestForQuotation::route('/{record}/edit'),
        ];
    }
}
