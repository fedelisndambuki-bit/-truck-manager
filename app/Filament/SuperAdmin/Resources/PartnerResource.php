<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\PartnerResource\Pages;
use App\Models\Partner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PartnerResource extends Resource
{
    protected static ?string $model = Partner::class;

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $navigationGroup = 'Landing Page Manager';

    protected static ?string $navigationLabel = 'Partners & Integrations';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\FileUpload::make('logo')
                    ->image()
                    ->directory('partners')
                    ->helperText('A transparent PNG/SVG, cropped tightly to the logo mark (no extra padding), displays best — the landing page renders every logo at the same size, so a logo with a lot of empty space around it will look smaller than the others.')
                    ->required(),
                Forms\Components\TextInput::make('website_url')
                    ->label('Website URL')
                    ->url()
                    ->maxLength(255),
                Forms\Components\Select::make('category')
                    ->options(Partner::CATEGORIES)
                    ->default('technology')
                    ->required()
                    ->helperText('Only use "Industry Partner" for a genuine, verified business partnership — this controls whether the landing page section is labeled "Partners & Integrations" or "Technology & Integrations".'),
                Forms\Components\TextInput::make('display_order')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Forms\Components\Toggle::make('status')
                    ->label('Active')
                    ->default(true)
                    ->required()
                    ->helperText('Only active partners are shown on the landing page.'),
                Forms\Components\Toggle::make('grayscale')
                    ->label('Display in grayscale')
                    ->helperText('Off shows the logo in its natural brand colors (recommended).')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->square(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Partner::CATEGORIES[$state] ?? $state),
                Tables\Columns\TextColumn::make('website_url')
                    ->label('Website')
                    ->limit(30),
                Tables\Columns\TextColumn::make('display_order')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('status')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\IconColumn::make('grayscale')
                    ->boolean(),
            ])
            ->defaultSort('display_order', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options(Partner::CATEGORIES),
                Tables\Filters\TernaryFilter::make('status')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPartners::route('/'),
            'create' => Pages\CreatePartner::route('/create'),
            'edit' => Pages\EditPartner::route('/{record}/edit'),
        ];
    }
}
