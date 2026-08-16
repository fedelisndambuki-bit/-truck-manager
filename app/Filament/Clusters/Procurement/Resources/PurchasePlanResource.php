<?php

namespace App\Filament\Clusters\Procurement\Resources;

use App\Filament\Clusters\Procurement;
use App\Filament\Clusters\Procurement\Resources\PurchasePlanResource\Pages;
use App\Models\PurchasePlan;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PurchasePlanResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = Procurement::class;

    public static function getModuleSlug(): ?string
    {
        return 'procurement.view';
    }

    protected static ?string $model = PurchasePlan::class;

    protected static ?string $slug = 'purchase-planning';

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Purchase Planning';

    protected static ?int $navigationSort = 1;

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Approved' => 'success', 'Rejected' => 'danger', 'Pending' => 'warning', default => 'gray',
        };
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['department']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Plan')->schema([
                    Forms\Components\TextInput::make('plan_number')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\TextInput::make('financial_year')->default((string) now()->year)->required(),
                    Forms\Components\Select::make('period_type')->options(array_combine(PurchasePlan::PERIOD_TYPES, PurchasePlan::PERIOD_TYPES))->default('Annual')->required(),
                    Forms\Components\Select::make('department_id')->relationship('department', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('business_unit_id')->label('Business Unit')->relationship('businessUnit', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('project_id')->relationship('project', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('extraction_site_id')->label('Quarry / Site')->relationship('extractionSite', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('warehouse_id')->relationship('warehouse', 'name')->searchable()->preload(),
                    Forms\Components\TextInput::make('category'),
                ])->columns(3),

                Forms\Components\Section::make('Budget & Estimates')->schema([
                    Forms\Components\TextInput::make('budget')->numeric()->prefix(session('currency', 'KES')),
                    Forms\Components\TextInput::make('estimated_quantity')->numeric(),
                    Forms\Components\TextInput::make('estimated_cost')->numeric()->prefix(session('currency', 'KES')),
                    Forms\Components\Select::make('priority')->options(array_combine(PurchasePlan::PRIORITIES, PurchasePlan::PRIORITIES))->default('Medium')->required(),
                    Forms\Components\Select::make('status')->options(array_combine(PurchasePlan::STATUSES, PurchasePlan::STATUSES))->default('Draft')->required(),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('plan_number')->label('Plan #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('financial_year')->label('FY'),
                Tables\Columns\TextColumn::make('period_type')->badge(),
                Tables\Columns\TextColumn::make('department.name')->placeholder('—'),
                Tables\Columns\TextColumn::make('category')->placeholder('—'),
                Tables\Columns\TextColumn::make('estimated_cost')->money(session('currency', 'KES'))->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(array_combine(PurchasePlan::STATUSES, PurchasePlan::STATUSES)),
                Tables\Filters\SelectFilter::make('financial_year')->options(fn () => PurchasePlan::query()->distinct()->pluck('financial_year', 'financial_year')->all()),
            ])
            ->actions([
                Tables\Actions\Action::make('generateRequisition')
                    ->label('Generate Requisition')
                    ->icon('heroicon-o-document-plus')
                    ->color('primary')
                    ->visible(fn (PurchasePlan $record) => $record->status === 'Approved')
                    ->action(function (PurchasePlan $record) {
                        $pr = $record->generateRequisition();
                        Notification::make()->title("Requisition {$pr->pr_number} created from this plan")->success()->send();
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchasePlans::route('/'),
            'create' => Pages\CreatePurchasePlan::route('/create'),
            'edit' => Pages\EditPurchasePlan::route('/{record}/edit'),
        ];
    }
}
