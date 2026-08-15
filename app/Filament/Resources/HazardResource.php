<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HazardResource\Pages;
use App\Models\Hazard;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HazardResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction', 'quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'safety.view';
    }

    protected static ?string $model = Hazard::class;

    protected static ?string $navigationIcon = 'heroicon-o-fire';

    protected static ?string $navigationLabel = 'Hazards';

    protected static ?string $navigationGroup = 'Safety & Incident Management';

    protected static ?int $navigationSort = 40;

    public static function getLikelihoodOptions(): array
    {
        return ['Rare' => 'Rare', 'Unlikely' => 'Unlikely', 'Possible' => 'Possible', 'Likely' => 'Likely', 'Almost Certain' => 'Almost Certain'];
    }

    public static function getImpactOptions(): array
    {
        return ['Negligible' => 'Negligible', 'Minor' => 'Minor', 'Moderate' => 'Moderate', 'Major' => 'Major', 'Catastrophic' => 'Catastrophic'];
    }

    public static function getStatusOptions(): array
    {
        return ['Open' => 'Open', 'In Progress' => 'In Progress', 'Controlled' => 'Controlled', 'Closed' => 'Closed'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Hazard Register Entry')->schema([
                    Forms\Components\TextInput::make('hazard_number')->label('Hazard ID')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\Select::make('project_id')->label('Project')->relationship('project', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('site_id')->label('Site')->relationship('site', 'name')->searchable()->preload()->required(),
                    Forms\Components\Select::make('hazard_type')->options(array_combine(Hazard::TYPES, Hazard::TYPES))->searchable()->required(),
                    Forms\Components\Textarea::make('description')->required()->columnSpanFull()->rows(2),
                ])->columns(3),

                Forms\Components\Section::make('Risk Rating')->schema([
                    Forms\Components\Select::make('likelihood')->options(self::getLikelihoodOptions())->default('Possible')->live()->required(),
                    Forms\Components\Select::make('impact')->options(self::getImpactOptions())->default('Moderate')->live()->required(),
                    Forms\Components\Placeholder::make('risk_rating_preview')
                        ->label('Computed Risk Rating')
                        ->content(fn (Forms\Get $get) => Hazard::calculateRiskRating($get('likelihood'), $get('impact'))),
                ])->columns(3),

                Forms\Components\Section::make('Controls')->schema([
                    Forms\Components\Textarea::make('existing_controls')->columnSpanFull()->rows(2),
                    Forms\Components\Textarea::make('recommended_controls')->columnSpanFull()->rows(2),
                    Forms\Components\Select::make('responsible_staff_id')->label('Responsible Person')->relationship('responsibleStaff', 'full_name')->searchable()->preload(),
                    Forms\Components\DatePicker::make('target_date'),
                    Forms\Components\Select::make('status')->options(self::getStatusOptions())->default('Open')->required(),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('hazard_number')->label('Hazard ID')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('site.name')->label('Site')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('hazard_type')->label('Type')->toggleable(),
                Tables\Columns\TextColumn::make('risk_rating')->badge()->color(fn (string $state) => match ($state) {
                    'Low' => 'success', 'Medium' => 'warning', 'High' => 'danger', 'Critical' => 'danger', default => 'gray',
                }),
                Tables\Columns\TextColumn::make('responsibleStaff.full_name')->label('Responsible')->toggleable(),
                Tables\Columns\TextColumn::make('target_date')->date()->sortable()->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'Open' => 'danger', 'In Progress' => 'warning', 'Controlled' => 'info', 'Closed' => 'success', default => 'gray',
                }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('risk_rating')->options([
                    'Low' => 'Low', 'Medium' => 'Medium', 'High' => 'High', 'Critical' => 'Critical',
                ]),
                Tables\Filters\SelectFilter::make('status')->options(self::getStatusOptions()),
                Tables\Filters\SelectFilter::make('site_id')->label('Site')->relationship('site', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->striped()
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHazards::route('/'),
            'create' => Pages\CreateHazard::route('/create'),
            'edit' => Pages\EditHazard::route('/{record}/edit'),
        ];
    }
}
