<?php

namespace App\Filament\Clusters\ProjectFinance\Resources;

use App\Filament\Clusters\ProjectFinance;
use App\Filament\Clusters\ProjectFinance\Resources\BudgetResource\Pages;
use App\Models\ProjectBudget;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class BudgetResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'project_finance.view';
    }

    protected static ?string $model = ProjectBudget::class;

    protected static ?string $cluster = ProjectFinance::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationLabel = 'Budgets';

    protected static ?int $navigationSort = 2;

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            'Approved' => 'success',
            'Submitted' => 'warning',
            'Rejected' => 'danger',
            default => 'gray',
        };
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['project', 'costCentre', 'costCode']);
    }

    public static function form(Form $form): Form
    {
        $currency = session('currency', 'KES');

        return $form
            ->schema([
                Forms\Components\Section::make('Budget Details')->schema([
                    Forms\Components\Select::make('project_id')
                        ->relationship('project', 'name')
                        ->searchable()->preload()->required()
                        ->live(),
                    Forms\Components\Select::make('site_id')
                        ->relationship('site', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('cost_centre_id')
                        ->label('Cost Centre')
                        ->relationship('costCentre', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('cost_code_id')
                        ->label('Cost Code')
                        ->relationship('costCode', 'name')
                        ->searchable()->preload(),
                    Forms\Components\TextInput::make('category')
                        ->helperText('e.g. Earthworks, Concrete, Labour, Overheads'),
                    Forms\Components\Select::make('status')
                        ->options([
                            'Draft' => 'Draft', 'Submitted' => 'Submitted',
                            'Approved' => 'Approved', 'Rejected' => 'Rejected',
                        ])->default('Draft')->required(),
                ])->columns(2),
                Forms\Components\Section::make('Amounts')->schema([
                    Forms\Components\TextInput::make('approved_budget')
                        ->numeric()->prefix($currency)->required(),
                    Forms\Components\TextInput::make('revised_budget')
                        ->numeric()->prefix($currency)
                        ->helperText('Leave 0 unless this budget has been formally revised.'),
                ])->columns(2),
                Forms\Components\Section::make('Notes')->schema([
                    Forms\Components\Textarea::make('notes')->columnSpanFull(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $currency = session('currency', 'KES');

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('budget_number')->label('Budget #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('project.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('costCentre.name')->label('Cost Centre')->toggleable(),
                Tables\Columns\TextColumn::make('costCode.name')->label('Cost Code')->toggleable(),
                Tables\Columns\TextColumn::make('category')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('approved_budget')->money($currency)->sortable(),
                Tables\Columns\TextColumn::make('revised_budget')->money($currency)->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('actual_cost')->label('Actual Cost')->money($currency)->sortable(),
                Tables\Columns\TextColumn::make('variance')->label('Variance')->money($currency)
                    ->color(fn (ProjectBudget $record) => $record->variance >= 0 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => static::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'Draft' => 'Draft', 'Submitted' => 'Submitted',
                    'Approved' => 'Approved', 'Rejected' => 'Rejected',
                ]),
                Tables\Filters\SelectFilter::make('project_id')->label('Project')
                    ->relationship('project', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('recalculate')
                    ->label('Recalculate')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->action(fn (ProjectBudget $record) => $record->recalculateActual()),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ProjectBudget $record) => $record->status === 'Submitted' && Auth::user()?->can('project_finance.approve'))
                    ->requiresConfirmation()
                    ->action(fn (ProjectBudget $record) => $record->approve()),
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
            'index' => Pages\ListBudgets::route('/'),
            'create' => Pages\CreateBudget::route('/create'),
            'edit' => Pages\EditBudget::route('/{record}/edit'),
        ];
    }
}
