<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RiskAssessmentResource\Pages;
use App\Models\RiskAssessment;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RiskAssessmentResource extends Resource
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

    protected static ?string $model = RiskAssessment::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Risk Assessments';

    protected static ?string $navigationGroup = 'Safety & Incident Management';

    protected static ?int $navigationSort = 50;

    public static function getScaleOptions(): array
    {
        return [1 => '1 - Very Low', 2 => '2 - Low', 3 => '3 - Medium', 4 => '4 - High', 5 => '5 - Very High'];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['site']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Assessment Details')->schema([
                    Forms\Components\TextInput::make('assessment_number')->label('Assessment #')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\Select::make('project_id')->label('Project')->relationship('project', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('site_id')->label('Site')->relationship('site', 'name')->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('activity')->required()->placeholder('e.g. Working at Height - Roof Installation'),
                    Forms\Components\Textarea::make('persons_at_risk')->columnSpanFull()->rows(2),
                    Forms\Components\Textarea::make('hazards')->label('Hazards Identified')->columnSpanFull()->rows(2),
                ])->columns(3),

                Forms\Components\Section::make('Risk Matrix — Initial Risk')->schema([
                    Forms\Components\Select::make('likelihood')->options(self::getScaleOptions())->default(3)->live()->required(),
                    Forms\Components\Select::make('severity')->options(self::getScaleOptions())->default(3)->live()->required(),
                    Forms\Components\Placeholder::make('risk_preview')
                        ->label('Risk Score / Level')
                        ->content(function (Forms\Get $get) {
                            $score = (int) $get('likelihood') * (int) $get('severity');

                            return $score . ' — ' . RiskAssessment::levelForScore($score);
                        }),
                    Forms\Components\Textarea::make('control_measures')->columnSpanFull()->rows(3),
                ])->columns(3),

                Forms\Components\Section::make('Residual Risk (After Controls)')->schema([
                    Forms\Components\Select::make('residual_likelihood')->options(self::getScaleOptions())->live(),
                    Forms\Components\Select::make('residual_severity')->options(self::getScaleOptions())->live(),
                    Forms\Components\Placeholder::make('residual_risk_preview')
                        ->label('Residual Score / Level')
                        ->content(function (Forms\Get $get) {
                            if (! $get('residual_likelihood') || ! $get('residual_severity')) {
                                return '—';
                            }
                            $score = (int) $get('residual_likelihood') * (int) $get('residual_severity');

                            return $score . ' — ' . RiskAssessment::levelForScore($score);
                        }),
                ])->columns(3),

                Forms\Components\Section::make('Approval')->schema([
                    Forms\Components\Select::make('assessed_by_id')->label('Assessed By')->relationship('assessedBy', 'full_name')->searchable()->preload(),
                    Forms\Components\DatePicker::make('review_date'),
                    Forms\Components\Select::make('status')->options([
                        'Draft' => 'Draft', 'Pending Approval' => 'Pending Approval', 'Approved' => 'Approved',
                        'Under Review' => 'Under Review', 'Expired' => 'Expired',
                    ])->default('Draft')->required(),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('assessment_number')->label('Assessment #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('site.name')->label('Site')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('activity')->limit(40)->searchable(),
                Tables\Columns\TextColumn::make('risk_level')->label('Initial Risk')->badge()->color(fn (string $state) => match ($state) {
                    'Low' => 'success', 'Medium' => 'warning', 'High' => 'danger', 'Critical' => 'danger', default => 'gray',
                }),
                Tables\Columns\TextColumn::make('residual_risk_level')->label('Residual Risk')->badge()->placeholder('—')->color(fn (?string $state) => match ($state) {
                    'Low' => 'success', 'Medium' => 'warning', 'High' => 'danger', 'Critical' => 'danger', default => 'gray',
                }),
                Tables\Columns\TextColumn::make('review_date')->date()->sortable()->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'Draft' => 'gray', 'Pending Approval' => 'warning', 'Approved' => 'success',
                    'Under Review' => 'info', 'Expired' => 'danger', default => 'gray',
                }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('risk_level')->options([
                    'Low' => 'Low', 'Medium' => 'Medium', 'High' => 'High', 'Critical' => 'Critical',
                ]),
                Tables\Filters\SelectFilter::make('status')->options([
                    'Draft' => 'Draft', 'Pending Approval' => 'Pending Approval', 'Approved' => 'Approved',
                    'Under Review' => 'Under Review', 'Expired' => 'Expired',
                ]),
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
            'index' => Pages\ListRiskAssessments::route('/'),
            'create' => Pages\CreateRiskAssessment::route('/create'),
            'edit' => Pages\EditRiskAssessment::route('/{record}/edit'),
        ];
    }
}
