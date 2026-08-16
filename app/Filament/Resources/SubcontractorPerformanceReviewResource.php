<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubcontractorPerformanceReviewResource\Pages;
use App\Models\SubcontractorPerformanceReview;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubcontractorPerformanceReviewResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'subcontractors.view';
    }

    protected static ?string $model = SubcontractorPerformanceReview::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationLabel = 'Performance Reviews';

    protected static ?string $navigationGroup = 'Subcontractor Management';

    protected static ?int $navigationSort = 14;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Review Details')->schema([
                    Forms\Components\Select::make('subcontractor_id')
                        ->label('Subcontractor')
                        ->relationship('subcontractor', 'company_name')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('contract_id')
                        ->label('Contract')
                        ->relationship('contract', 'contract_number')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('project_id')
                        ->label('Project')
                        ->relationship('project', 'name')
                        ->searchable()->preload(),
                    Forms\Components\DatePicker::make('review_date')->required()->default(now()),
                    Forms\Components\Select::make('reviewed_by_id')
                        ->label('Reviewed By')
                        ->relationship('reviewedBy', 'full_name')
                        ->searchable()->preload(),
                ])->columns(2),
                Forms\Components\Section::make('Ratings (1-5)')->schema([
                    Forms\Components\TextInput::make('quality_of_work_rating')->numeric()->minValue(1)->maxValue(5)->default(3),
                    Forms\Components\TextInput::make('timeliness_rating')->numeric()->minValue(1)->maxValue(5)->default(3),
                    Forms\Components\TextInput::make('safety_rating')->numeric()->minValue(1)->maxValue(5)->default(3),
                    Forms\Components\TextInput::make('communication_rating')->numeric()->minValue(1)->maxValue(5)->default(3),
                    Forms\Components\TextInput::make('cost_control_rating')->numeric()->minValue(1)->maxValue(5)->default(3),
                    Forms\Components\TextInput::make('issue_resolution_rating')->numeric()->minValue(1)->maxValue(5)->default(3),
                ])->columns(3),
                Forms\Components\Section::make('Comments')->schema([
                    Forms\Components\Textarea::make('comments')->columnSpanFull(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('subcontractor.company_name')->label('Subcontractor')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('review_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('quality_of_work_rating')->label('Quality')->suffix('/5'),
                Tables\Columns\TextColumn::make('timeliness_rating')->label('Timeliness')->suffix('/5'),
                Tables\Columns\TextColumn::make('safety_rating')->label('Safety')->suffix('/5'),
                Tables\Columns\TextColumn::make('overall_rating')->label('Overall')->suffix('/5')->badge()
                    ->color(fn ($state) => $state >= 4 ? 'success' : ($state >= 3 ? 'warning' : 'danger')),
                Tables\Columns\TextColumn::make('reviewedBy.full_name')->label('Reviewed By')->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subcontractor_id')->label('Subcontractor')->relationship('subcontractor', 'company_name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('review_date', 'desc')
            ->striped();
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['subcontractor', 'reviewedBy']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubcontractorPerformanceReviews::route('/'),
            'create' => Pages\CreateSubcontractorPerformanceReview::route('/create'),
            'edit' => Pages\EditSubcontractorPerformanceReview::route('/{record}/edit'),
        ];
    }
}
