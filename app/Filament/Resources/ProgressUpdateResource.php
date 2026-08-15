<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProgressUpdateResource\Pages;
use App\Models\ProgressUpdate;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProgressUpdateResource extends Resource
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

    protected static ?string $model = ProgressUpdate::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Progress Tracking';

    protected static ?string $navigationGroup = 'Subcontractor Management';

    protected static ?int $navigationSort = 9;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Progress Update')->schema([
                    Forms\Components\Select::make('contract_id')
                        ->label('Contract')
                        ->relationship('contract', 'contract_number')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('milestone_id')
                        ->label('Linked Milestone')
                        ->relationship('milestone', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('period_type')
                        ->options(['Daily' => 'Daily', 'Weekly' => 'Weekly', 'Monthly' => 'Monthly'])
                        ->default('Weekly')->required(),
                    Forms\Components\DatePicker::make('date')->required()->default(now()),
                    Forms\Components\TextInput::make('percent_complete')->numeric()->suffix('%')->minValue(0)->maxValue(100)->default(0),
                    Forms\Components\Textarea::make('completed_activities')->columnSpanFull(),
                    Forms\Components\Textarea::make('remaining_activities')->columnSpanFull(),
                    Forms\Components\FileUpload::make('photos')->image()->multiple()->columnSpanFull(),
                    Forms\Components\Textarea::make('comments')->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('contract.contract_number')->label('Contract')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('contract.subcontractor.company_name')->label('Subcontractor')->searchable(),
                Tables\Columns\TextColumn::make('period_type')->label('Period'),
                Tables\Columns\TextColumn::make('date')->date()->sortable(),
                Tables\Columns\TextColumn::make('percent_complete')->suffix('%')->label('% Complete')->sortable(),
                Tables\Columns\IconColumn::make('supervisor_approved')->boolean()->label('Approved'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('period_type')->options(['Daily' => 'Daily', 'Weekly' => 'Weekly', 'Monthly' => 'Monthly']),
                Tables\Filters\TernaryFilter::make('supervisor_approved')->label('Approved'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ProgressUpdate $record) => ! $record->supervisor_approved)
                    ->requiresConfirmation()
                    ->action(fn (ProgressUpdate $record) => $record->update([
                        'supervisor_approved' => true,
                    ])),
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
            'index' => Pages\ListProgressUpdates::route('/'),
            'create' => Pages\CreateProgressUpdate::route('/create'),
            'edit' => Pages\EditProgressUpdate::route('/{record}/edit'),
        ];
    }
}
