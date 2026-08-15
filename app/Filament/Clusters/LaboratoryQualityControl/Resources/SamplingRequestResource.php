<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources;

use App\Filament\Clusters\LaboratoryQualityControl;
use App\Filament\Clusters\LaboratoryQualityControl\Resources\LabSampleResource;
use App\Filament\Clusters\LaboratoryQualityControl\Resources\SamplingRequestResource\Pages;
use App\Models\SamplingRequest;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * "Sampling Requests" — the 7 real trigger reasons per spec. Only
 * 'Production Completed' and 'New Stockpile' are wired as automatic
 * Observer-driven hooks (ProductionApprovalObserver/StockpileObserver); the
 * other 5 (Customer Order, Dispatch Approval, Routine Inspection, Random
 * Inspection, Complaint Investigation) are selectable manually here rather
 * than automated cross-module hooks the spec doesn't unambiguously require.
 */
class SamplingRequestResource extends Resource
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

    protected static ?string $model = SamplingRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Sampling Requests';

    protected static ?int $navigationSort = 2;

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Completed' => 'success',
            'Cancelled' => 'danger',
            'In Progress', 'Assigned' => 'info',
            default => 'warning',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Sampling Request')->schema([
                    Forms\Components\TextInput::make('request_number')->label('Request #')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\Select::make('trigger')->options(array_combine(SamplingRequest::TRIGGERS, SamplingRequest::TRIGGERS))->required(),
                    Forms\Components\Select::make('priority')->options(array_combine(SamplingRequest::PRIORITIES, SamplingRequest::PRIORITIES))->default('Normal')->required(),
                    Forms\Components\Select::make('requested_by_id')->label('Requested By')
                        ->relationship('requestedBy', 'full_name')->searchable()->preload(),
                    Forms\Components\Select::make('assigned_technician_id')->label('Assigned Technician')
                        ->relationship('assignedTechnician', 'full_name')->searchable()->preload(),
                    Forms\Components\DatePicker::make('due_date')->default(now()->addDay()),
                    Forms\Components\Select::make('status')->options(array_combine(SamplingRequest::STATUSES, SamplingRequest::STATUSES))->default('Pending')->required(),
                    Forms\Components\Textarea::make('notes')->columnSpanFull(),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('due_date')
            ->columns([
                Tables\Columns\TextColumn::make('request_number')->label('Request #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('trigger')->badge(),
                Tables\Columns\TextColumn::make('priority')->badge(),
                Tables\Columns\TextColumn::make('assignedTechnician.full_name')->label('Technician')->placeholder('Unassigned'),
                Tables\Columns\TextColumn::make('due_date')->date('d M Y')->sortable()->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(array_combine(SamplingRequest::STATUSES, SamplingRequest::STATUSES)),
                Tables\Filters\SelectFilter::make('trigger')->options(array_combine(SamplingRequest::TRIGGERS, SamplingRequest::TRIGGERS)),
            ])
            ->actions([
                Tables\Actions\Action::make('assignTechnician')
                    ->label('Assign')
                    ->icon('heroicon-o-user')
                    ->color('info')
                    ->visible(fn (SamplingRequest $record) => $record->status === 'Pending')
                    ->form([
                        Forms\Components\Select::make('assigned_technician_id')->label('Technician')
                            ->relationship('assignedTechnician', 'full_name')->searchable()->preload()->required(),
                    ])
                    ->action(function (SamplingRequest $record, array $data) {
                        $record->update(['assigned_technician_id' => $data['assigned_technician_id'], 'status' => 'Assigned']);
                        Notification::make()->title('Technician assigned')->success()->send();
                    }),
                Tables\Actions\Action::make('createSample')
                    ->label('Create Sample')
                    ->icon('heroicon-o-beaker')
                    ->color('success')
                    ->visible(fn (SamplingRequest $record) => in_array($record->status, ['Assigned', 'In Progress']))
                    ->url(fn (SamplingRequest $record) => LabSampleResource::getUrl('create', ['sampling_request_id' => $record->id])),
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
        return ['request_number'];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSamplingRequests::route('/'),
            'create' => Pages\CreateSamplingRequest::route('/create'),
            'edit' => Pages\EditSamplingRequest::route('/{record}/edit'),
        ];
    }
}
