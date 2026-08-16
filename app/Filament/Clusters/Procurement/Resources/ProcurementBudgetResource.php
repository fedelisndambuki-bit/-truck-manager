<?php

namespace App\Filament\Clusters\Procurement\Resources;

use App\Filament\Clusters\Procurement;
use App\Filament\Clusters\Procurement\Resources\ProcurementBudgetResource\Pages;
use App\Models\ProcurementBudget;
use App\Services\BudgetValidationService;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ProcurementBudgetResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = Procurement::class;

    public static function getModuleSlug(): ?string
    {
        return 'procurement.view';
    }

    protected static ?string $model = ProcurementBudget::class;

    protected static ?string $slug = 'budget-control';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Budget Control';

    protected static ?int $navigationSort = 19;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['businessUnit', 'department']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Budget')->schema([
                    Forms\Components\TextInput::make('budget_number')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\TextInput::make('financial_year')->default((string) now()->year)->required(),
                    Forms\Components\Select::make('period_type')->options(array_combine(ProcurementBudget::PERIOD_TYPES, ProcurementBudget::PERIOD_TYPES))->default('Annual')->required(),
                    Forms\Components\TextInput::make('category'),
                    Forms\Components\TextInput::make('approved_budget')->numeric()->required()->prefix(session('currency', 'KES')),
                    Forms\Components\Select::make('status')->options(array_combine(ProcurementBudget::STATUSES, ProcurementBudget::STATUSES))->default('Active')->required(),
                ])->columns(3),

                Forms\Components\Section::make('Dimension (at least one recommended)')->schema([
                    Forms\Components\Select::make('business_unit_id')->label('Business Unit')->relationship('businessUnit', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('department_id')->relationship('department', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('cost_centre_id')->label('Cost Center')->relationship('costCentre', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('project_id')->relationship('project', 'name')->searchable()->preload(),
                ])->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('budget_number')->label('Budget #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('financial_year')->label('FY'),
                Tables\Columns\TextColumn::make('category')->placeholder('—'),
                Tables\Columns\TextColumn::make('businessUnit.name')->label('Business Unit')->placeholder('—'),
                Tables\Columns\TextColumn::make('department.name')->label('Department')->placeholder('—'),
                Tables\Columns\TextColumn::make('approved_budget')->money(session('currency', 'KES')),
                Tables\Columns\TextColumn::make('remaining')->label('Remaining')
                    ->state(function (ProcurementBudget $record) {
                        $result = app(BudgetValidationService::class)->validate(
                            Auth::user()?->company_id,
                            [
                                'business_unit_id' => $record->business_unit_id,
                                'department_id' => $record->department_id,
                                'cost_centre_id' => $record->cost_centre_id,
                                'project_id' => $record->project_id,
                            ],
                            0,
                            $record->financial_year
                        );

                        return $result['remaining'];
                    })
                    ->money(session('currency', 'KES')),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => $state === 'Active' ? 'success' : 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('financial_year')->options(fn () => ProcurementBudget::query()->distinct()->pluck('financial_year', 'financial_year')->all()),
                Tables\Filters\SelectFilter::make('status')->options(array_combine(ProcurementBudget::STATUSES, ProcurementBudget::STATUSES)),
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
            'index' => Pages\ListProcurementBudgets::route('/'),
            'create' => Pages\CreateProcurementBudget::route('/create'),
            'edit' => Pages\EditProcurementBudget::route('/{record}/edit'),
        ];
    }
}
