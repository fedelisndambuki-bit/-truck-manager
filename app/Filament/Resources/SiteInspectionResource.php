<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteInspectionResource\Pages;
use App\Models\SiteInspection;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SiteInspectionResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    protected static ?string $model = SiteInspection::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Site Inspections';

    protected static ?string $navigationGroup = 'Construction';

    protected static ?int $navigationSort = 9;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Inspection Details')->schema([
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
                    Forms\Components\Select::make('type')
                        ->options([
                            'General' => 'General',
                            'Structural' => 'Structural',
                            'Electrical' => 'Electrical',
                            'Plumbing' => 'Plumbing',
                        ])
                        ->default('General')
                        ->required(),
                    Forms\Components\Select::make('result')
                        ->options([
                            'Pass' => 'Pass',
                            'Fail' => 'Fail',
                            'Needs Review' => 'Needs Review',
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
                Tables\Columns\TextColumn::make('inspector.full_name')->label('Inspector'),
                Tables\Columns\TextColumn::make('date')->date()->sortable(),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('result')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pass' => 'success',
                        'Fail' => 'danger',
                        'Needs Review' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('result')->options([
                    'Pass' => 'Pass',
                    'Fail' => 'Fail',
                    'Needs Review' => 'Needs Review',
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
            'index' => Pages\ListSiteInspections::route('/'),
            'create' => Pages\CreateSiteInspection::route('/create'),
            'edit' => Pages\EditSiteInspection::route('/{record}/edit'),
        ];
    }
}
