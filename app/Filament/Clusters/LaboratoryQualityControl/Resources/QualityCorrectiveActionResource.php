<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources;

use App\Filament\Clusters\LaboratoryQualityControl;
use App\Filament\Clusters\LaboratoryQualityControl\Resources\QualityCorrectiveActionResource\Pages;
use App\Models\QualityCorrectiveAction;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * "Corrective Actions" (CAPA) — over the new QualityCorrectiveAction model,
 * deliberately NOT named "CorrectiveAction" since that class/table already
 * exists as the Safety/HSE CAPA model (confirmed collision during planning).
 */
class QualityCorrectiveActionResource extends Resource
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

    protected static ?string $model = QualityCorrectiveAction::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Corrective Actions';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'corrective-actions';

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Closed', 'Verified' => 'success',
            'Completed' => 'info',
            'In Progress' => 'warning',
            default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Corrective Action')->schema([
                    Forms\Components\TextInput::make('action_number')->label('Action #')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\Select::make('non_conformance_id')->label('Related NCR')
                        ->relationship('nonConformance', 'ncr_number')->searchable()->preload()->required(),
                    Forms\Components\Select::make('responsible_person_id')->label('Responsible Person')
                        ->relationship('responsiblePerson', 'full_name')->searchable()->preload(),
                    Forms\Components\DatePicker::make('due_date'),
                    Forms\Components\TextInput::make('progress_percent')->label('Progress %')->numeric()->minValue(0)->maxValue(100)->default(0),
                    Forms\Components\Select::make('status')->options(array_combine(QualityCorrectiveAction::STATUSES, QualityCorrectiveAction::STATUSES))->default('Open')->required(),
                    Forms\Components\Textarea::make('verification_notes')->columnSpanFull(),
                    Forms\Components\Textarea::make('effectiveness_review')->columnSpanFull(),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('due_date')
            ->columns([
                Tables\Columns\TextColumn::make('action_number')->label('Action #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('nonConformance.ncr_number')->label('NCR #')->placeholder('—'),
                Tables\Columns\TextColumn::make('responsiblePerson.full_name')->label('Responsible')->placeholder('—'),
                Tables\Columns\TextColumn::make('progress_percent')->label('Progress')->suffix('%'),
                Tables\Columns\TextColumn::make('due_date')->date('d M Y')->placeholder('—'),
                Tables\Columns\IconColumn::make('overdue')->label('Overdue')->boolean()
                    ->state(fn (QualityCorrectiveAction $record) => $record->isOverdue()),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(array_combine(QualityCorrectiveAction::STATUSES, QualityCorrectiveAction::STATUSES)),
            ])
            ->actions([
                Tables\Actions\Action::make('approveClosure')
                    ->label('Approve Closure')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (QualityCorrectiveAction $record) => $record->status === 'Completed')
                    ->form([
                        Forms\Components\Select::make('closure_approved_by_id')->label('Approved By')
                            ->relationship('closureApprovedBy', 'full_name')->searchable()->preload()->required(),
                    ])
                    ->action(function (QualityCorrectiveAction $record, array $data) {
                        $record->approveClosure($data['closure_approved_by_id']);
                        Notification::make()->title('Corrective action closed')->success()->send();
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
        return ['action_number'];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQualityCorrectiveActions::route('/'),
            'create' => Pages\CreateQualityCorrectiveAction::route('/create'),
            'view' => Pages\ViewQualityCorrectiveAction::route('/{record}'),
            'edit' => Pages\EditQualityCorrectiveAction::route('/{record}/edit'),
        ];
    }
}
