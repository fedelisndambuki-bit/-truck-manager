<?php

namespace App\Filament\Clusters\ProjectFinance\Resources;

use App\Filament\Clusters\ProjectFinance;
use App\Filament\Clusters\ProjectFinance\Resources\BoqItemResource\Pages;
use App\Models\BoqItem;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BoqItemResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'project_finance.view';
    }

    protected static ?string $model = BoqItem::class;

    protected static ?string $cluster = ProjectFinance::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'BOQ Management';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'item_code';

    public static function form(Form $form): Form
    {
        $currency = session('currency', 'KES');

        return $form
            ->schema([
                Forms\Components\Section::make('Item Details')->schema([
                    Forms\Components\Select::make('project_id')
                        ->relationship('project', 'name')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('cost_code_id')
                        ->label('Cost Code')
                        ->relationship('costCode', 'name')
                        ->searchable()->preload(),
                    Forms\Components\TextInput::make('item_code')->required(),
                    Forms\Components\TextInput::make('unit')->default('m2')->required(),
                    Forms\Components\Textarea::make('description')->required()->columnSpanFull(),
                ])->columns(2),
                Forms\Components\Section::make('Quantities & Rates')->schema([
                    Forms\Components\TextInput::make('quantity')->numeric()->required(),
                    Forms\Components\TextInput::make('rate')->numeric()->prefix($currency)->required(),
                    Forms\Components\TextInput::make('completed_quantity')->numeric()->default(0),
                    Forms\Components\TextInput::make('variation_quantity')->numeric()->default(0),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $currency = session('currency', 'KES');

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('item_code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('description')->limit(40)->searchable(),
                Tables\Columns\TextColumn::make('project.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('unit'),
                Tables\Columns\TextColumn::make('quantity')->numeric(2)->sortable(),
                Tables\Columns\TextColumn::make('rate')->money($currency),
                Tables\Columns\TextColumn::make('amount')->money($currency)->sortable(),
                Tables\Columns\TextColumn::make('completed_quantity')->label('Completed Qty')->numeric(2),
                Tables\Columns\TextColumn::make('percent_complete')->label('% Complete')->suffix('%')
                    ->color(fn (BoqItem $record) => $record->percent_complete >= 100 ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('variation_quantity')->label('Variation Qty')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')->label('Project')->relationship('project', 'name'),
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
            'index' => Pages\ListBoqItems::route('/'),
            'create' => Pages\CreateBoqItem::route('/create'),
            'edit' => Pages\EditBoqItem::route('/{record}/edit'),
        ];
    }
}
