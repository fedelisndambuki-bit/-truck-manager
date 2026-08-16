<?php

namespace App\Filament\Clusters\ProjectFinance\Resources;

use App\Filament\Clusters\ProjectFinance;
use App\Filament\Clusters\ProjectFinance\Resources\CostCentreResource\Pages;
use App\Models\CostCentre;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CostCentreResource extends Resource
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

    protected static ?string $model = CostCentre::class;

    protected static ?string $cluster = ProjectFinance::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Cost Centres';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['project', 'site', 'manager']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()->schema([
                    Forms\Components\TextInput::make('code')->required()->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\Select::make('project_id')
                        ->relationship('project', 'name')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('site_id')
                        ->relationship('site', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('manager_staff_id')
                        ->label('Manager')
                        ->relationship('manager', 'full_name')
                        ->searchable()->preload(),
                    Forms\Components\Textarea::make('description')->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('project.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('site.name')->toggleable(),
                Tables\Columns\TextColumn::make('manager.full_name')->label('Manager')->toggleable(),
                Tables\Columns\TextColumn::make('budgets_sum_approved_budget')
                    ->label('Total Budget')
                    ->sum('budgets', 'approved_budget')
                    ->money(session('currency', 'KES')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')->label('Project')->relationship('project', 'name'),
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
            'index' => Pages\ListCostCentres::route('/'),
            'create' => Pages\CreateCostCentre::route('/create'),
            'edit' => Pages\EditCostCentre::route('/{record}/edit'),
        ];
    }
}
