<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaterialAllocationResource\Pages;
use App\Models\MaterialAllocation;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MaterialAllocationResource extends Resource
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

    protected static ?string $model = MaterialAllocation::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';

    protected static ?string $navigationLabel = 'Material Allocation';

    protected static ?string $navigationGroup = 'Materials Management';

    protected static ?int $navigationSort = 13;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['material', 'project', 'site']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Allocation Details')->schema([
                    Forms\Components\Select::make('material_id')
                        ->label('Material')
                        ->relationship('material', 'name')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('project_id')
                        ->label('Project')
                        ->relationship('project', 'name')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('site_id')
                        ->label('Site')
                        ->relationship('site', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('department_id')
                        ->label('Department')
                        ->relationship('department', 'name')
                        ->searchable()->preload(),
                    Forms\Components\TextInput::make('activity')->label('Activity / Construction Phase'),
                    Forms\Components\TextInput::make('allocated_quantity')->numeric()->required(),
                    Forms\Components\Select::make('status')->options([
                        'Active' => 'Active', 'Completed' => 'Completed', 'Cancelled' => 'Cancelled',
                    ])->default('Active')->required(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('material.name')->label('Material')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('project.name')->label('Project')->searchable(),
                Tables\Columns\TextColumn::make('site.name')->label('Site')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('activity')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('allocated_quantity')->label('Allocated')->numeric(2),
                Tables\Columns\TextColumn::make('consumed_quantity')->label('Consumed')->numeric(2),
                Tables\Columns\TextColumn::make('remaining_quantity')->label('Remaining')->numeric(2)
                    ->state(fn (MaterialAllocation $record) => $record->remaining_quantity)
                    ->color(fn (MaterialAllocation $record) => $record->remaining_quantity <= 0 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'Active' => 'success', 'Completed' => 'info', 'Cancelled' => 'gray', default => 'gray',
                }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')->label('Project')->relationship('project', 'name'),
                Tables\Filters\SelectFilter::make('status')->options(['Active' => 'Active', 'Completed' => 'Completed', 'Cancelled' => 'Cancelled']),
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
            'index' => Pages\ListMaterialAllocations::route('/'),
            'create' => Pages\CreateMaterialAllocation::route('/create'),
            'edit' => Pages\EditMaterialAllocation::route('/{record}/edit'),
        ];
    }
}
