<?php

namespace App\Filament\Clusters\Finance\Resources;

use App\Filament\Clusters\Finance;
use App\Filament\Clusters\Finance\Resources\ProfitCentreResource\Pages;
use App\Models\ProfitCentre;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProfitCentreResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'finance.profit_centres';
    }

    protected static ?string $model = ProfitCentre::class;

    protected static ?string $cluster = Finance::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationLabel = 'Profit Centres';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getDimensionOptions(): array
    {
        return array_combine(
            $types = ['BusinessUnit', 'Region', 'Branch', 'Quarry', 'Project', 'Customer', 'Product'],
            $types
        );
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()->schema([
                    Forms\Components\TextInput::make('code')->required()->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\Select::make('dimension_type')
                        ->options(self::getDimensionOptions())->required(),
                    Forms\Components\TextInput::make('dimension_label')
                        ->label('Dimension')
                        ->helperText('e.g. the project, region or customer name this profit centre tracks.'),
                    Forms\Components\Select::make('manager_staff_id')
                        ->label('Manager')
                        ->relationship('manager', 'full_name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('status')
                        ->options(['Active' => 'Active', 'Inactive' => 'Inactive'])
                        ->default('Active')->required(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('dimension_type')->badge(),
                Tables\Columns\TextColumn::make('dimension_label')->label('Dimension')->toggleable(),
                Tables\Columns\TextColumn::make('manager.full_name')->label('Manager')->toggleable(),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state) => $state === 'Active' ? 'success' : 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('dimension_type')->options(self::getDimensionOptions()),
                Tables\Filters\SelectFilter::make('status')->options(['Active' => 'Active', 'Inactive' => 'Inactive']),
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
            'index' => Pages\ListProfitCentres::route('/'),
            'create' => Pages\CreateProfitCentre::route('/create'),
            'edit' => Pages\EditProfitCentre::route('/{record}/edit'),
        ];
    }
}
