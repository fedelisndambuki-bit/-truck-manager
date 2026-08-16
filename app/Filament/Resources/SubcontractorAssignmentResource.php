<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubcontractorAssignmentResource\Pages;
use App\Models\SubcontractorAssignment;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubcontractorAssignmentResource extends Resource
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

    protected static ?string $model = SubcontractorAssignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'Project Assignments';

    protected static ?string $navigationGroup = 'Subcontractor Management';

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Assignment Details')->schema([
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
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('site_id')
                        ->label('Site')
                        ->relationship('site', 'name')
                        ->searchable()->preload(),
                    Forms\Components\TextInput::make('construction_phase'),
                    Forms\Components\TextInput::make('work_area'),
                    Forms\Components\Select::make('site_manager_id')
                        ->label('Site Manager')
                        ->relationship('siteManager', 'full_name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('supervisor_id')
                        ->label('Supervisor')
                        ->relationship('supervisor', 'full_name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('status')->options(['Active' => 'Active', 'Completed' => 'Completed'])->default('Active')->required(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('subcontractor.company_name')->label('Subcontractor')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('project.name')->label('Project')->searchable(),
                Tables\Columns\TextColumn::make('site.name')->label('Site')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('construction_phase')->label('Phase')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('siteManager.full_name')->label('Site Manager')->placeholder('—'),
                Tables\Columns\TextColumn::make('supervisor.full_name')->label('Supervisor')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => $state === 'Active' ? 'success' : 'info'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(['Active' => 'Active', 'Completed' => 'Completed']),
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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['subcontractor', 'project', 'site', 'siteManager', 'supervisor']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubcontractorAssignments::route('/'),
            'create' => Pages\CreateSubcontractorAssignment::route('/create'),
            'edit' => Pages\EditSubcontractorAssignment::route('/{record}/edit'),
        ];
    }
}
