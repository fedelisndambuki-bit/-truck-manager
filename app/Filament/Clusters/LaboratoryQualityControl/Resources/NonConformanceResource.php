<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources;

use App\Filament\Clusters\LaboratoryQualityControl;
use App\Filament\Clusters\LaboratoryQualityControl\Resources\NonConformanceResource\Pages;
use App\Models\NonConformance;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * "Non-Conformance" — NCRs created manually or (in the demo seeder) from a
 * failed LabTest result. Each NCR can spawn multiple QualityCorrectiveAction
 * records via correctiveActions().
 */
class NonConformanceResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = LaboratoryQualityControl::class;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'lab.view';
    }

    protected static ?string $model = NonConformance::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationLabel = 'Non-Conformance';

    protected static ?int $navigationSort = 9;

    public static function getSeverityColor(string $state): string
    {
        return match ($state) {
            'Critical' => 'danger',
            'Major' => 'warning',
            default => 'gray',
        };
    }

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Closed', 'Verified' => 'success',
            'In Progress' => 'info',
            default => 'warning',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Non-Conformance')->schema([
                    Forms\Components\TextInput::make('ncr_number')->label('NCR #')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\TextInput::make('source')->label('Source'),
                    Forms\Components\Select::make('lab_sample_id')->label('Related Sample')
                        ->relationship('sample', 'sample_number')->searchable()->preload(),
                    Forms\Components\Select::make('severity')->options(array_combine(NonConformance::SEVERITIES, NonConformance::SEVERITIES))->default('Minor')->required(),
                    Forms\Components\Select::make('responsible_person_id')->label('Responsible Person')
                        ->relationship('responsiblePerson', 'full_name')->searchable()->preload(),
                    Forms\Components\DatePicker::make('due_date'),
                    Forms\Components\Select::make('status')->options(array_combine(NonConformance::STATUSES, NonConformance::STATUSES))->default('Open')->required(),
                    Forms\Components\Textarea::make('description')->columnSpanFull(),
                    Forms\Components\Textarea::make('root_cause')->label('Root Cause')->columnSpanFull(),
                    Forms\Components\Textarea::make('immediate_action')->label('Immediate Action')->columnSpanFull(),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('ncr_number')->label('NCR #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('sample.sample_number')->label('Sample')->placeholder('—'),
                Tables\Columns\TextColumn::make('description')->limit(40),
                Tables\Columns\TextColumn::make('severity')->badge()->color(fn (string $state): string => self::getSeverityColor($state)),
                Tables\Columns\TextColumn::make('responsiblePerson.full_name')->label('Responsible')->placeholder('—'),
                Tables\Columns\TextColumn::make('due_date')->date('d M Y')->placeholder('—'),
                Tables\Columns\TextColumn::make('correctiveActions_count')->label('CAPAs')->counts('correctiveActions'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(array_combine(NonConformance::STATUSES, NonConformance::STATUSES)),
                Tables\Filters\SelectFilter::make('severity')->options(array_combine(NonConformance::SEVERITIES, NonConformance::SEVERITIES)),
            ])
            ->actions([
                Tables\Actions\Action::make('close')
                    ->label('Close')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (NonConformance $record) => $record->status !== 'Closed')
                    ->action(function (NonConformance $record) {
                        $record->close();
                        Notification::make()->title('NCR closed')->success()->send();
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->striped();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['ncr_number', 'description'];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNonConformances::route('/'),
            'create' => Pages\CreateNonConformance::route('/create'),
            'view' => Pages\ViewNonConformance::route('/{record}'),
            'edit' => Pages\EditNonConformance::route('/{record}/edit'),
        ];
    }
}
