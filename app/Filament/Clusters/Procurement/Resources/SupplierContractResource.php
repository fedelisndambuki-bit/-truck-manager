<?php

namespace App\Filament\Clusters\Procurement\Resources;

use App\Filament\Clusters\Procurement;
use App\Filament\Clusters\Procurement\Resources\SupplierContractResource\Pages;
use App\Filament\Clusters\Procurement\Resources\SupplierContractResource\RelationManagers;
use App\Models\SupplierContract;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupplierContractResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = Procurement::class;

    public static function getModuleSlug(): ?string
    {
        return 'procurement.view';
    }

    protected static ?string $model = SupplierContract::class;

    protected static ?string $slug = 'contracts';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Contracts';

    protected static ?int $navigationSort = 8;

    public static function getTypeOptions(): array
    {
        return array_combine(SupplierContract::TYPES, SupplierContract::TYPES);
    }

    public static function getStatusOptions(): array
    {
        return array_combine(SupplierContract::STATUSES, SupplierContract::STATUSES);
    }

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Active' => 'success', 'Expired' => 'danger', 'Terminated' => 'danger', 'Renewed' => 'info', default => 'gray',
        };
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['supplier']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Contract')->schema([
                    Forms\Components\TextInput::make('contract_number')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\Select::make('supplier_id')->relationship('supplier', 'company_name')->searchable()->preload()->required(),
                    Forms\Components\Select::make('contract_type')->options(self::getTypeOptions())->default('Standard')->required(),
                    Forms\Components\TextInput::make('contract_value')->numeric()->prefix(session('currency', 'KES')),
                    Forms\Components\TextInput::make('currency')->default('KES'),
                    Forms\Components\Select::make('status')->options(self::getStatusOptions())->default('Draft')->required(),
                ])->columns(3),

                Forms\Components\Section::make('Dates')->schema([
                    Forms\Components\DatePicker::make('effective_date'),
                    Forms\Components\DatePicker::make('expiry_date'),
                    Forms\Components\DatePicker::make('renewal_date'),
                ])->columns(3),

                Forms\Components\Section::make('Service Levels & KPIs')->schema([
                    Forms\Components\KeyValue::make('service_levels')->keyLabel('Item')->valueLabel('Level')->columnSpanFull(),
                    Forms\Components\KeyValue::make('kpis')->keyLabel('KPI')->valueLabel('Target')->columnSpanFull(),
                    Forms\Components\Textarea::make('notes')->columnSpanFull(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('contract_number')->label('Contract #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('supplier.company_name')->label('Supplier')->searchable(),
                Tables\Columns\TextColumn::make('contract_type')->label('Type')->badge(),
                Tables\Columns\TextColumn::make('contract_value')->money(session('currency', 'KES'))->placeholder('—'),
                Tables\Columns\TextColumn::make('expiry_date')->date()->placeholder('—')->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(self::getStatusOptions()),
                Tables\Filters\SelectFilter::make('contract_type')->label('Type')->options(self::getTypeOptions()),
            ])
            ->actions([
                Tables\Actions\Action::make('submitForApproval')
                    ->label('Submit for Approval')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (SupplierContract $record) => $record->status === 'Draft' && ! $record->isApprovalPending())
                    ->action(function (SupplierContract $record) {
                        $record->submitForApproval('supplier-contract-approval', [
                            'title' => 'Supplier Contract — ' . $record->contract_number,
                            'metadata' => ['amount' => (float) $record->contract_value],
                        ]);
                        Notification::make()->title('Submitted to the Workflow & Approvals engine')->success()->send();
                    }),
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

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupplierContracts::route('/'),
            'create' => Pages\CreateSupplierContract::route('/create'),
            'edit' => Pages\EditSupplierContract::route('/{record}/edit'),
        ];
    }
}
