<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JobVacancyResource\Pages;
use App\Filament\Resources\JobVacancyResource\RelationManagers\ApplicationsRelationManager;
use App\Models\JobVacancy;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class JobVacancyResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction', 'quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'hr.recruitment_access';
    }

    protected static ?string $model = JobVacancy::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = 'Recruitment';

    protected static ?string $navigationGroup = 'Labour & HR Management';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Vacancy Details')->schema([
                    Forms\Components\TextInput::make('title')->required(),
                    Forms\Components\Select::make('department_id')
                        ->label('Department')
                        ->relationship('department', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('position_id')
                        ->label('Position')
                        ->relationship('position', 'title')
                        ->searchable()->preload(),
                    Forms\Components\TextInput::make('openings_count')->numeric()->default(1)->required(),
                    Forms\Components\Select::make('status')->options(['Open' => 'Open', 'Closed' => 'Closed'])->default('Open')->required(),
                    Forms\Components\Textarea::make('description')->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('department.name')->label('Department')->placeholder('—'),
                Tables\Columns\TextColumn::make('position.title')->label('Position')->placeholder('—'),
                Tables\Columns\TextColumn::make('openings_count')->label('Openings'),
                Tables\Columns\TextColumn::make('applications_count')->counts('applications')->label('Applications'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => $state === 'Open' ? 'success' : 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(['Open' => 'Open', 'Closed' => 'Closed']),
            ])
            ->actions([
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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['department', 'position']);
    }

    public static function getRelations(): array
    {
        return [
            ApplicationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJobVacancies::route('/'),
            'create' => Pages\CreateJobVacancy::route('/create'),
            'edit' => Pages\EditJobVacancy::route('/{record}/edit'),
        ];
    }
}
