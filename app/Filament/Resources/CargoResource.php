<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CargoResource\Pages;
use App\Models\Cargo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

use App\Traits\HasModuleAccess;

class CargoResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'fleet-management';
    }

    public static function getRequiredIndustries(): array
    {
        return ['trucking'];
    }
    protected static ?string $model = Cargo::class;

    // Distinct icon: cube/box for cargo
    protected static ?string $navigationIcon = 'heroicon-o-cube';

    public static function form(Form $form): Form
    {
        // Inside form()
        return $form->schema([
            Forms\Components\Card::make()->schema([
                Forms\Components\Select::make('client_id')
                    ->relationship('client', 'company_name')
                    ->required()->searchable()->preload(),
                Forms\Components\TextInput::make('description')->required(),
                Forms\Components\TextInput::make('cargo_type')->required()->placeholder('e.g. Loose Cargo, Containers'),
                Forms\Components\TextInput::make('weight')->numeric()->suffix('Tons')->required(),
                Forms\Components\TextInput::make('value')->numeric()->prefix(session('currency', 'KES')),
                Forms\Components\Textarea::make('handling_instructions')->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.company_name')->label('Client')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('description')->searchable(),
                Tables\Columns\TextColumn::make('cargo_type')->searchable(),
                Tables\Columns\TextColumn::make('weight')->suffix(' Tons'),
                Tables\Columns\TextColumn::make('value')->label('Value')
                    ->money(session('currency', 'KES'))
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])->striped();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user  = auth()->user();
        if ($user) {
            $query->where('company_id', $user->company_id);
        }
        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCargos::route('/'),
            'create' => Pages\CreateCargo::route('/create'),
            'edit' => Pages\EditCargo::route('/{record}/edit'),
        ];
    }
}
