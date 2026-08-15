<?php

namespace App\Filament\Clusters\BlastManagement\Resources;

use App\Filament\Clusters\BlastManagement;
use App\Filament\Clusters\BlastManagement\Resources\BlastPlanResource\Pages;
use App\Models\BlastPlan;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BlastPlanResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = BlastManagement::class;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'blast.view';
    }

    protected static ?string $model = BlastPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Blast Plans';

    protected static ?int $navigationSort = 2;

    public static function getStatusOptions(): array
    {
        return array_combine(BlastPlan::STATUSES, BlastPlan::STATUSES);
    }

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Draft' => 'gray',
            'Submitted' => 'warning',
            'Approved' => 'info',
            'Executed' => 'success',
            'Closed' => 'success',
            'Cancelled' => 'danger',
            default => 'gray',
        };
    }

    public static function getPriorityOptions(): array
    {
        return array_combine(BlastPlan::PRIORITIES, BlastPlan::PRIORITIES);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Assignment')->schema([
                    Forms\Components\TextInput::make('blast_number')->label('Blast #')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\TextInput::make('blast_name')->required(),
                    Forms\Components\Select::make('extraction_site_id')->label('Quarry')->relationship('extractionSite', 'name')->searchable()->preload()->live()->required(),
                    Forms\Components\Select::make('pit_id')->label('Pit')->relationship('pit', 'name', fn ($query, Forms\Get $get) => $query->where('extraction_site_id', $get('extraction_site_id')))->searchable()->preload(),
                    Forms\Components\Select::make('quarry_face_id')->label('Quarry Face')->relationship('quarryFace', 'name', fn ($query, Forms\Get $get) => $query->where('extraction_site_id', $get('extraction_site_id')))->searchable()->preload(),
                    Forms\Components\TextInput::make('bench'),
                    Forms\Components\Select::make('blast_engineer_id')->label('Blast Engineer')->relationship('blastEngineer', 'full_name')->searchable()->preload(),
                    Forms\Components\DatePicker::make('date_planned')->required()->default(now()),
                    Forms\Components\Select::make('priority')->options(self::getPriorityOptions())->default('Medium')->required(),
                    Forms\Components\Select::make('status')->options(self::getStatusOptions())->default('Draft')->required(),
                ])->columns(3),

                Forms\Components\Section::make('Blast Design')->schema([
                    Forms\Components\TextInput::make('drill_pattern'),
                    Forms\Components\TextInput::make('burden')->numeric()->suffix('m'),
                    Forms\Components\TextInput::make('spacing')->numeric()->suffix('m'),
                    Forms\Components\TextInput::make('bench_height')->numeric()->suffix('m'),
                    Forms\Components\TextInput::make('sub_drill')->numeric()->suffix('m'),
                ])->columns(3),

                Forms\Components\Section::make('Scheduling')->schema([
                    Forms\Components\DatePicker::make('scheduled_date'),
                    Forms\Components\TimePicker::make('scheduled_time'),
                    Forms\Components\Select::make('safety_officer_id')->label('Safety Officer')->relationship('safetyOfficer', 'full_name')->searchable()->preload(),
                    Forms\Components\Select::make('blast_team')
                        ->label('Blast Team')
                        ->multiple()
                        ->options(fn () => \App\Models\Staff::query()->pluck('full_name', 'id'))
                        ->searchable()->preload(),
                    Forms\Components\TextInput::make('security_team'),
                    Forms\Components\TextInput::make('emergency_team'),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('blast_number')->label('Blast #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('blast_name')->searchable(),
                Tables\Columns\TextColumn::make('extractionSite.name')->label('Quarry')->toggleable(),
                Tables\Columns\TextColumn::make('blastEngineer.full_name')->label('Blast Engineer')->placeholder('—'),
                Tables\Columns\TextColumn::make('date_planned')->date()->sortable(),
                Tables\Columns\TextColumn::make('priority')->badge()->color(fn (string $state): string => match ($state) {
                    'Low' => 'gray', 'Medium' => 'info', 'High' => 'warning', 'Urgent' => 'danger', default => 'gray',
                }),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->defaultSort('date_planned', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(self::getStatusOptions()),
                Tables\Filters\SelectFilter::make('priority')->options(self::getPriorityOptions()),
                Tables\Filters\SelectFilter::make('extraction_site_id')->label('Quarry')->relationship('extractionSite', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('submitForApproval')
                    ->label('Submit for Approval')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (BlastPlan $record) => $record->status === 'Draft' && ! $record->isApprovalPending())
                    ->action(function (BlastPlan $record) {
                        $record->update(['status' => 'Submitted']);
                        $record->submitForApproval('blast-plan-approval', [
                            'title' => 'Blast Plan — ' . $record->blast_number,
                            'metadata' => ['blast_plan_id' => $record->id],
                        ]);
                        Notification::make()->title('Submitted to the Workflow & Approvals engine')->success()->send();
                    }),
                Tables\Actions\Action::make('viewApproval')
                    ->label('View Approval')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->visible(fn (BlastPlan $record) => $record->currentApproval() !== null)
                    ->url(fn (BlastPlan $record) => \App\Filament\Clusters\WorkflowApprovals\Resources\ApprovalRequestResource\Pages\ViewApprovalRequest::getUrl(['record' => $record->currentApproval()])),
                Tables\Actions\Action::make('markExecuted')
                    ->label('Mark Executed')
                    ->icon('heroicon-o-bolt')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (BlastPlan $record) => $record->status === 'Approved')
                    ->action(function (BlastPlan $record) {
                        $record->update(['status' => 'Executed']);
                        Notification::make()->title('Blast marked as executed — record execution details next')->success()->send();
                    }),
                Tables\Actions\Action::make('close')
                    ->label('Close')
                    ->icon('heroicon-o-lock-closed')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (BlastPlan $record) => $record->status === 'Executed')
                    ->action(function (BlastPlan $record) {
                        $record->update(['status' => 'Closed']);
                        Notification::make()->title('Blast plan closed')->success()->send();
                    }),
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
            'index' => Pages\ListBlastPlans::route('/'),
            'create' => Pages\CreateBlastPlan::route('/create'),
            'view' => Pages\ViewBlastPlan::route('/{record}'),
            'edit' => Pages\EditBlastPlan::route('/{record}/edit'),
        ];
    }
}
