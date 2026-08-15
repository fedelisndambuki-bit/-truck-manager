<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QualityInspectionResource\Pages;
use App\Models\QualityInspection;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class QualityInspectionResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    protected static ?string $model = QualityInspection::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $navigationLabel = 'Quality Control';

    protected static ?string $navigationGroup = 'Construction';

    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Quality Check Details')->schema([
                    Forms\Components\Select::make('site_id')
                        ->label('Site')
                        ->relationship('site', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Select::make('inspector_id')
                        ->label('Inspector')
                        ->relationship('inspector', 'full_name')
                        ->searchable()
                        ->preload(),
                    Forms\Components\DatePicker::make('date')->required()->default(now()),
                    Forms\Components\TextInput::make('checklist_item')->label('Checklist Item'),
                    Forms\Components\Select::make('result')
                        ->options([
                            'Pass' => 'Pass',
                            'Fail' => 'Fail',
                        ])
                        ->default('Pass')
                        ->required(),
                ])->columns(2),

                Forms\Components\Textarea::make('notes')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('site.name')->label('Site')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('checklist_item')->label('Item'),
                Tables\Columns\TextColumn::make('date')->date()->sortable(),
                Tables\Columns\TextColumn::make('result')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pass' => 'success',
                        'Fail' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('result')->options([
                    'Pass' => 'Pass',
                    'Fail' => 'Fail',
                ]),
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
            ->defaultSort('date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQualityInspections::route('/'),
            'create' => Pages\CreateQualityInspection::route('/create'),
            'edit' => Pages\EditQualityInspection::route('/{record}/edit'),
        ];
    }
}
