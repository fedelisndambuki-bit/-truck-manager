<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaterialReturnResource\Pages;
use App\Models\MaterialReturn;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class MaterialReturnResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'materials.view';
    }

    protected static ?string $model = MaterialReturn::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static ?string $navigationLabel = 'Returns';

    protected static ?string $navigationGroup = 'Materials Management';

    protected static ?int $navigationSort = 15;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Return Details')->schema([
                    Forms\Components\Select::make('material_id')
                        ->label('Material')
                        ->relationship('material', 'name')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('warehouse_id')
                        ->label('Warehouse')
                        ->relationship('warehouse', 'name')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('project_id')
                        ->label('Project')
                        ->relationship('project', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('site_id')
                        ->label('Site')
                        ->relationship('site', 'name')
                        ->searchable()->preload(),
                    Forms\Components\TextInput::make('return_quantity')->numeric()->required(),
                    Forms\Components\Select::make('condition')->options(['Good' => 'Good', 'Damaged' => 'Damaged'])->default('Good')->required(),
                    Forms\Components\Textarea::make('reason')->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('material.name')->label('Material')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('warehouse.name')->label('Warehouse'),
                Tables\Columns\TextColumn::make('return_quantity')->label('Quantity')->numeric(2),
                Tables\Columns\TextColumn::make('condition')->badge()->color(fn (string $state) => $state === 'Good' ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => $state === 'Approved' ? 'success' : 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(['Pending' => 'Pending', 'Approved' => 'Approved']),
                Tables\Filters\SelectFilter::make('condition')->options(['Good' => 'Good', 'Damaged' => 'Damaged']),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (MaterialReturn $record) {
                        $record->approve();
                        Notification::make()->title('Return approved and stock credited')->success()->send();
                    })
                    ->visible(fn (MaterialReturn $record) => $record->status === 'Pending'
                        && (Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('materials.approve'))),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMaterialReturns::route('/'),
            'create' => Pages\CreateMaterialReturn::route('/create'),
            'edit' => Pages\EditMaterialReturn::route('/{record}/edit'),
        ];
    }
}
