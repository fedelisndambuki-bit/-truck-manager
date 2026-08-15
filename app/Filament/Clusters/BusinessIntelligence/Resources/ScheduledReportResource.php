<?php

namespace App\Filament\Clusters\BusinessIntelligence\Resources;

use App\Filament\Clusters\BusinessIntelligence;
use App\Filament\Clusters\BusinessIntelligence\Resources\ScheduledReportResource\Pages;
use App\Models\ScheduledReport;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ScheduledReportResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'bi.schedule';
    }

    protected static ?string $model = ScheduledReport::class;

    protected static ?string $cluster = BusinessIntelligence::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Scheduled Reports';

    protected static ?int $navigationSort = 16;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Schedule Details')->schema([
                    Forms\Components\TextInput::make('name')->required()->columnSpanFull(),
                    Forms\Components\Select::make('source_type')->options([
                        'custom_report' => 'Custom Report', 'existing_report' => 'Existing Report',
                    ])->default('custom_report')->required()->live(),
                    Forms\Components\Select::make('custom_report_id')
                        ->label('Custom Report')
                        ->relationship('customReport', 'name')
                        ->searchable()->preload()
                        ->visible(fn (Forms\Get $get) => $get('source_type') === 'custom_report')
                        ->required(fn (Forms\Get $get) => $get('source_type') === 'custom_report'),
                    Forms\Components\TextInput::make('source_key')
                        ->label('Report Name')
                        ->helperText('e.g. "Executive Financial Dashboard"')
                        ->visible(fn (Forms\Get $get) => $get('source_type') === 'existing_report')
                        ->required(fn (Forms\Get $get) => $get('source_type') === 'existing_report'),
                    Forms\Components\Select::make('frequency')->options([
                        'Daily' => 'Daily', 'Weekly' => 'Weekly', 'Monthly' => 'Monthly',
                        'Quarterly' => 'Quarterly', 'Yearly' => 'Yearly',
                    ])->default('Weekly')->required(),
                    Forms\Components\Select::make('delivery_method')->options([
                        'Email' => 'Email', 'PDF' => 'PDF', 'Excel' => 'Excel', 'Dashboard Link' => 'Dashboard Link',
                    ])->default('Email')->required(),
                    Forms\Components\Select::make('format')->options([
                        'pdf' => 'PDF', 'csv' => 'CSV', 'excel' => 'Excel', 'word' => 'Word', 'json' => 'JSON',
                    ])->default('pdf')->required(),
                    Forms\Components\TagsInput::make('recipients')
                        ->label('Recipient Emails')
                        ->placeholder('Add email and press Enter')
                        ->columnSpanFull(),
                    Forms\Components\Toggle::make('is_active')->default(true),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('frequency')->badge()->color('info'),
                Tables\Columns\TextColumn::make('delivery_method')->toggleable(),
                Tables\Columns\TextColumn::make('format')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('next_run_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('last_run_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('frequency')->options([
                    'Daily' => 'Daily', 'Weekly' => 'Weekly', 'Monthly' => 'Monthly',
                    'Quarterly' => 'Quarterly', 'Yearly' => 'Yearly',
                ]),
                Tables\Filters\TernaryFilter::make('is_active'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListScheduledReports::route('/'),
            'create' => Pages\CreateScheduledReport::route('/create'),
            'edit' => Pages\EditScheduledReport::route('/{record}/edit'),
        ];
    }
}
