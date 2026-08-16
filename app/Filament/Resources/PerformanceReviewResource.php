<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PerformanceReviewResource\Pages;
use App\Models\PerformanceReview;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PerformanceReviewResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction', 'quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'hr.view';
    }

    protected static ?string $model = PerformanceReview::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Performance Management';

    protected static ?string $navigationGroup = 'Labour & HR Management';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Performance Review')->schema([
                    Forms\Components\Select::make('staff_id')
                        ->label('Employee')
                        ->relationship('staff', 'full_name')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('reviewer_id')
                        ->label('Reviewer')
                        ->relationship('reviewer', 'full_name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('period_type')
                        ->options(['Monthly' => 'Monthly', 'Quarterly' => 'Quarterly', 'Annual' => 'Annual'])
                        ->default('Quarterly')->required(),
                    Forms\Components\DatePicker::make('date')->default(now())->required(),
                    Forms\Components\TextInput::make('rating')->numeric()->step(0.1)->minValue(0)->maxValue(5)->suffix('/ 5'),
                    Forms\Components\Textarea::make('strengths')->columnSpanFull(),
                    Forms\Components\Textarea::make('areas_for_improvement')->columnSpanFull(),
                    Forms\Components\Textarea::make('goals')->columnSpanFull(),
                    Forms\Components\Textarea::make('comments')->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('staff.full_name')->label('Employee')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('period_type')->badge(),
                Tables\Columns\TextColumn::make('date')->date()->sortable(),
                Tables\Columns\TextColumn::make('rating')->suffix(' / 5')->badge()->color(fn (?string $state) => match (true) {
                    $state === null => 'gray',
                    (float) $state >= 4 => 'success',
                    (float) $state >= 3 => 'info',
                    (float) $state >= 2 => 'warning',
                    default => 'danger',
                }),
                Tables\Columns\TextColumn::make('reviewer.full_name')->label('Reviewer')->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('period_type')->options(['Monthly' => 'Monthly', 'Quarterly' => 'Quarterly', 'Annual' => 'Annual']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc')
            ->striped();
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['staff', 'reviewer']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPerformanceReviews::route('/'),
            'create' => Pages\CreatePerformanceReview::route('/create'),
            'edit' => Pages\EditPerformanceReview::route('/{record}/edit'),
        ];
    }
}
