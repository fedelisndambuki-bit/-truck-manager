<?php

namespace App\Filament\Clusters\BusinessIntelligence\Resources;

use App\Filament\Clusters\BusinessIntelligence;
use App\Filament\Clusters\BusinessIntelligence\Resources\CustomReportResource\Pages;
use App\Models\CustomReport;
use App\Services\Construction\BusinessIntelligenceService;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CustomReportResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'bi.view';
    }

    protected static ?string $model = CustomReport::class;

    protected static ?string $cluster = BusinessIntelligence::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Custom Reports';

    protected static ?int $navigationSort = 15;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['createdBy']);
    }

    public static function form(Form $form): Form
    {
        $service = app(BusinessIntelligenceService::class);

        return $form
            ->schema([
                Forms\Components\Section::make('Report Definition')->schema([
                    Forms\Components\TextInput::make('name')->required()->columnSpanFull(),
                    Forms\Components\Select::make('data_source')
                        ->options($service->dataSourceOptions())
                        ->required()->live()
                        ->afterStateUpdated(fn (Forms\Set $set) => $set('fields', [])),
                    Forms\Components\Select::make('chart_type')
                        ->label('Chart (optional)')
                        ->options(['bar' => 'Bar', 'line' => 'Line', 'doughnut' => 'Doughnut', 'pie' => 'Pie']),
                ])->columns(2),
                Forms\Components\Section::make('Fields')->schema([
                    Forms\Components\CheckboxList::make('fields')
                        ->label('Drag & drop equivalent — select the fields to include')
                        ->options(function (Forms\Get $get) use ($service) {
                            $def = $service->dataSourceDefinition((string) $get('data_source'));

                            return $def ? array_combine($def['columns'], $def['columns']) : [];
                        })
                        ->columns(3)
                        ->required(),
                ]),
                Forms\Components\Section::make('Filters')->schema([
                    Forms\Components\Repeater::make('filters')
                        ->schema([
                            Forms\Components\TextInput::make('field')->required(),
                            Forms\Components\Select::make('operator')->options([
                                '=' => 'equals', '!=' => 'not equals', '>' => 'greater than',
                                '<' => 'less than', 'like' => 'contains',
                            ])->default('=')->required(),
                            Forms\Components\TextInput::make('value')->required(),
                        ])
                        ->columns(3)
                        ->addActionLabel('Add filter')
                        ->columnSpanFull(),
                ]),
                Forms\Components\Section::make('Grouping, Sorting & Aggregation')->schema([
                    Forms\Components\TextInput::make('group_by')->label('Group by field'),
                    Forms\Components\Select::make('aggregate_function')->options([
                        'sum' => 'Sum', 'count' => 'Count', 'avg' => 'Average', 'min' => 'Minimum', 'max' => 'Maximum',
                    ]),
                    Forms\Components\TextInput::make('sort_by')->label('Sort by field'),
                    Forms\Components\Select::make('sort_direction')->options(['asc' => 'Ascending', 'desc' => 'Descending'])->default('asc'),
                ])->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('data_source')->badge()->color('info'),
                Tables\Columns\TextColumn::make('chart_type')->badge()->color('gray')->toggleable(),
                Tables\Columns\TextColumn::make('createdBy.name')->label('Created By')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('data_source')->options(app(BusinessIntelligenceService::class)->dataSourceOptions()),
            ])
            ->actions([
                Tables\Actions\Action::make('run')
                    ->label('Run')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->url(fn (CustomReport $record) => Pages\RunCustomReport::getUrl(['record' => $record])),
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
            'index' => Pages\ListCustomReports::route('/'),
            'create' => Pages\CreateCustomReport::route('/create'),
            'edit' => Pages\EditCustomReport::route('/{record}/edit'),
            'run' => Pages\RunCustomReport::route('/{record}/run'),
        ];
    }
}
