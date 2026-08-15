<?php

namespace App\Filament\Clusters\ProjectFinance\Resources;

use App\Filament\Clusters\ProjectFinance;
use App\Filament\Clusters\ProjectFinance\Resources\CostCodeResource\Pages;
use App\Models\CostCode;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CostCodeResource extends Resource
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

    protected static ?string $model = CostCode::class;

    protected static ?string $cluster = ProjectFinance::class;

    protected static ?string $navigationIcon = 'heroicon-o-hashtag';

    protected static ?string $navigationLabel = 'Cost Codes';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()->schema([
                    Forms\Components\TextInput::make('code')->required()->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\Select::make('category')->options([
                        'Site Establishment' => 'Site Establishment', 'Earthworks' => 'Earthworks',
                        'Concrete' => 'Concrete', 'Steel' => 'Steel', 'Masonry' => 'Masonry',
                        'Roofing' => 'Roofing', 'Electrical' => 'Electrical', 'Mechanical' => 'Mechanical',
                        'Plumbing' => 'Plumbing', 'Finishes' => 'Finishes', 'Equipment' => 'Equipment',
                        'Labour' => 'Labour', 'Subcontractors' => 'Subcontractors', 'Overheads' => 'Overheads',
                    ])->searchable(),
                    Forms\Components\Select::make('parent_id')
                        ->label('Parent Cost Code')
                        ->relationship('parent', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Toggle::make('is_active')->default(true),
                    Forms\Components\Textarea::make('description')->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('parent.name')->label('Parent')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')->options([
                    'Site Establishment' => 'Site Establishment', 'Earthworks' => 'Earthworks',
                    'Concrete' => 'Concrete', 'Steel' => 'Steel', 'Masonry' => 'Masonry',
                    'Roofing' => 'Roofing', 'Electrical' => 'Electrical', 'Mechanical' => 'Mechanical',
                    'Plumbing' => 'Plumbing', 'Finishes' => 'Finishes', 'Equipment' => 'Equipment',
                    'Labour' => 'Labour', 'Subcontractors' => 'Subcontractors', 'Overheads' => 'Overheads',
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
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCostCodes::route('/'),
            'create' => Pages\CreateCostCode::route('/create'),
            'edit' => Pages\EditCostCode::route('/{record}/edit'),
        ];
    }
}
