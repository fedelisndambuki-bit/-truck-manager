<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources;

use App\Filament\Clusters\LaboratoryQualityControl;
use App\Filament\Clusters\LaboratoryQualityControl\Resources\LabSampleResource\Pages;
use App\Models\LabSample;
use App\Services\Quarry\TraceabilityService;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Tabs\Tab;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Samples" — the LIMS flagship. Full sample lifecycle: Collected ->
 * Received -> Testing -> Approved/Rejected -> Archived.
 */
class LabSampleResource extends Resource
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

    protected static ?string $model = LabSample::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationLabel = 'Samples';

    protected static ?int $navigationSort = 1;

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Approved' => 'success',
            'Testing', 'Received' => 'info',
            'Rejected' => 'danger',
            'Archived' => 'gray',
            default => 'warning',
        };
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['material', 'stockpile']);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Tabs::make('Lab Sample')->tabs([
                Tab::make('Overview')->schema([
                    Section::make()->schema([
                        TextEntry::make('sample_number')->label('Sample #'),
                        TextEntry::make('sample_type')->badge(),
                        TextEntry::make('material.name')->label('Product')->placeholder('—'),
                        TextEntry::make('product_grade')->placeholder('—'),
                        TextEntry::make('extractionSite.name')->label('Quarry')->placeholder('—'),
                        TextEntry::make('crushingPlant.name')->label('Plant')->placeholder('—'),
                        TextEntry::make('stockpile.label')->label('Stockpile')->placeholder('—'),
                        TextEntry::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
                    ])->columns(4),
                ]),

                Tab::make('Collection')->schema([
                    Section::make()->schema([
                        TextEntry::make('batch_number')->label('Batch Number')->placeholder('—'),
                        TextEntry::make('production_number')->label('Production Number')->placeholder('—'),
                        TextEntry::make('blast_number')->label('Blast Number')->placeholder('—'),
                        TextEntry::make('collectedBy.full_name')->label('Collected By')->placeholder('—'),
                        TextEntry::make('collection_date')->date('d M Y')->placeholder('—'),
                        TextEntry::make('collection_time')->label('Collection Time')->placeholder('—'),
                        TextEntry::make('collection_location')->label('Location')->placeholder('—'),
                        TextEntry::make('gps_lat')->label('GPS Latitude')->placeholder('—'),
                        TextEntry::make('gps_lng')->label('GPS Longitude')->placeholder('—'),
                        TextEntry::make('samplingRequest.request_number')->label('Sampling Request')->placeholder('—'),
                    ])->columns(3),
                ]),

                Tab::make('Tests')->schema([
                    RepeatableEntry::make('tests')->hiddenLabel()->schema([
                        TextEntry::make('test_number')->label('Test #'),
                        TextEntry::make('testMethod.method_name')->label('Method')->placeholder('—'),
                        TextEntry::make('technician.full_name')->label('Technician')->placeholder('—'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('result')->badge()->color(fn (?string $state) => LabTestResource::getResultColor($state))->placeholder('—'),
                    ])->columns(5)->columnSpanFull(),
                ]),

                Tab::make('Approval')->schema([
                    Section::make()->schema([
                        TextEntry::make('isApprovalPending')->label('Approval Pending')
                            ->state(fn (LabSample $record) => $record->isApprovalPending() ? 'Yes' : 'No'),
                        TextEntry::make('isApproved')->label('Approved')
                            ->state(fn (LabSample $record) => $record->isApproved() ? 'Yes' : 'No'),
                    ])->columns(2),
                    RepeatableEntry::make('approvalRequests')->label('Approval History')->schema([
                        TextEntry::make('reference_number'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('submitted_at')->dateTime()->placeholder('—'),
                        TextEntry::make('completed_at')->dateTime()->placeholder('—'),
                    ])->columns(4)->columnSpanFull(),
                ]),

                Tab::make('Certificates')->schema([
                    RepeatableEntry::make('certificates')->hiddenLabel()->schema([
                        TextEntry::make('certificate_number')->label('Certificate #'),
                        TextEntry::make('type')->badge(),
                        TextEntry::make('customer.company_name')->label('Customer')->placeholder('—'),
                        TextEntry::make('status')->badge(),
                    ])->columns(4)->columnSpanFull(),
                ]),

                Tab::make('Traceability')->schema([
                    RepeatableEntry::make('trace')
                        ->hiddenLabel()
                        ->state(fn (LabSample $record) => app(TraceabilityService::class)->traceSample($record))
                        ->schema([
                            TextEntry::make('stage')->label('Stage')->weight('bold'),
                            IconEntry::make('found')->label('Recorded')->boolean(),
                            TextEntry::make('label')->label('Detail'),
                        ])->columns(3)->columnSpanFull(),
                ]),

                Tab::make('Documents')->schema([
                    RepeatableEntry::make('documents')->hiddenLabel()->schema([
                        TextEntry::make('category')->badge(),
                        TextEntry::make('file_path')->label('File')->formatStateUsing(fn ($state) => $state ? basename($state) : '—'),
                    ])->columns(2)->columnSpanFull(),
                ]),

                Tab::make('History')->schema([
                    Section::make()->schema([
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('updated_at')->label('Last Updated')->dateTime(),
                        TextEntry::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
                    ])->columns(3),
                    RepeatableEntry::make('nonConformances')->label('Non-Conformances')->schema([
                        TextEntry::make('ncr_number')->label('NCR #'),
                        TextEntry::make('severity')->badge(),
                        TextEntry::make('status')->badge(),
                    ])->columns(3)->columnSpanFull(),
                    RepeatableEntry::make('qualityHolds')->label('Quality Holds')->schema([
                        TextEntry::make('hold_number')->label('Hold #'),
                        TextEntry::make('reason')->badge(),
                        TextEntry::make('status')->badge(),
                    ])->columns(3)->columnSpanFull(),
                ]),
            ])->columnSpanFull(),
        ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Sample Details')->schema([
                    Forms\Components\TextInput::make('sample_number')->label('Sample #')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\Select::make('sampling_request_id')->label('Sampling Request')
                        ->relationship('samplingRequest', 'request_number')->searchable()->preload(),
                    Forms\Components\Select::make('sample_type')->options(array_combine(LabSample::SAMPLE_TYPES, LabSample::SAMPLE_TYPES))->required(),
                    Forms\Components\Select::make('material_id')->label('Product')
                        ->relationship('material', 'name')->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('product_grade')->label('Product Grade'),
                    Forms\Components\Select::make('status')->options(array_combine(LabSample::STATUSES, LabSample::STATUSES))->default('Collected')->required(),
                ])->columns(3),

                Forms\Components\Section::make('Source & Traceability')->schema([
                    Forms\Components\Select::make('extraction_site_id')->label('Quarry')
                        ->relationship('extractionSite', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('crushing_plant_id')->label('Crushing Plant')
                        ->relationship('crushingPlant', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('stockpile_id')->label('Stockpile')
                        ->relationship('stockpile', 'label')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->label ?? ('Stockpile #' . $record->id))
                        ->searchable()->preload(),
                    Forms\Components\TextInput::make('batch_number')->label('Batch Number'),
                    Forms\Components\TextInput::make('production_number')->label('Production Number'),
                    Forms\Components\TextInput::make('blast_number')->label('Blast Number'),
                ])->columns(3),

                Forms\Components\Section::make('Collection')->schema([
                    Forms\Components\Select::make('collected_by_id')->label('Collected By')
                        ->relationship('collectedBy', 'full_name')->searchable()->preload(),
                    Forms\Components\DatePicker::make('collection_date')->default(now()),
                    Forms\Components\TextInput::make('collection_time')->label('Collection Time')->placeholder('e.g. 14:30'),
                    Forms\Components\TextInput::make('collection_location')->label('Collection Location'),
                    Forms\Components\TextInput::make('gps_lat')->label('GPS Latitude')->numeric(),
                    Forms\Components\TextInput::make('gps_lng')->label('GPS Longitude')->numeric(),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('sample_number')->label('Sample #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('sample_type')->badge(),
                Tables\Columns\TextColumn::make('material.name')->label('Product')->placeholder('—'),
                Tables\Columns\TextColumn::make('stockpile.label')->label('Stockpile')->placeholder('—'),
                Tables\Columns\TextColumn::make('batch_number')->placeholder('—'),
                Tables\Columns\TextColumn::make('collection_date')->date('d M Y')->sortable()->placeholder('—'),
                Tables\Columns\TextColumn::make('tests_count')->label('Tests')->counts('tests'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(array_combine(LabSample::STATUSES, LabSample::STATUSES)),
                Tables\Filters\SelectFilter::make('sample_type')->options(array_combine(LabSample::SAMPLE_TYPES, LabSample::SAMPLE_TYPES)),
            ])
            ->actions([
                Tables\Actions\Action::make('markReceived')
                    ->label('Mark Received')
                    ->icon('heroicon-o-inbox-arrow-down')
                    ->color('info')
                    ->visible(fn (LabSample $record) => $record->status === 'Collected')
                    ->action(function (LabSample $record) {
                        $record->markReceived();
                        Notification::make()->title('Sample marked as received')->success()->send();
                    }),
                Tables\Actions\Action::make('submitForApproval')
                    ->label('Submit for Quality Approval')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (LabSample $record) => in_array($record->status, ['Received', 'Testing']) && ! $record->isApprovalPending())
                    ->action(function (LabSample $record) {
                        $record->submitForQualityApproval();
                        Notification::make()->title('Submitted to the Workflow & Approvals engine')->success()->send();
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
        return ['sample_number', 'batch_number', 'production_number', 'blast_number'];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLabSamples::route('/'),
            'create' => Pages\CreateLabSample::route('/create'),
            'edit' => Pages\EditLabSample::route('/{record}/edit'),
        ];
    }
}
