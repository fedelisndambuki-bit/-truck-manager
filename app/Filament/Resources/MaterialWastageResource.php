<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaterialWastageResource\Pages;
use App\Models\MaterialWastage;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class MaterialWastageResource extends Resource
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

    protected static ?string $model = MaterialWastage::class;

    protected static ?string $navigationIcon = 'heroicon-o-trash';

    protected static ?string $navigationLabel = 'Wastage';

    protected static ?string $navigationGroup = 'Materials Management';

    protected static ?int $navigationSort = 16;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['material', 'project']);
    }

    public static function getWasteTypeOptions(): array
    {
        return ['Damaged' => 'Damaged', 'Lost' => 'Lost', 'Expired' => 'Expired', 'Overuse' => 'Overuse'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Wastage Details')->schema([
                    Forms\Components\Select::make('material_id')
                        ->label('Material')
                        ->relationship('material', 'name')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('warehouse_id')
                        ->label('Warehouse')
                        ->relationship('warehouse', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('project_id')
                        ->label('Project')
                        ->relationship('project', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('site_id')
                        ->label('Site')
                        ->relationship('site', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('waste_type')->options(self::getWasteTypeOptions())->default('Damaged')->required(),
                    Forms\Components\TextInput::make('quantity')->numeric()->required(),
                    Forms\Components\Textarea::make('reason')->columnSpanFull(),
                    Forms\Components\Textarea::make('corrective_action')->columnSpanFull(),
                    Forms\Components\FileUpload::make('photos')
                        ->image()
                        ->multiple()
                        ->disk('public')
                        ->directory('wastage-photos')
                        ->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('material.name')->label('Material')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('waste_type')->badge()->color(fn (string $state) => match ($state) {
                    'Damaged' => 'warning', 'Lost' => 'danger', 'Expired' => 'gray', 'Overuse' => 'info', default => 'gray',
                }),
                Tables\Columns\TextColumn::make('quantity')->numeric(2),
                Tables\Columns\TextColumn::make('waste_percentage')->label('Waste %')->suffix('%')->placeholder('—')
                    ->state(fn (MaterialWastage $record) => $record->waste_percentage),
                Tables\Columns\TextColumn::make('project.name')->label('Project')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => $state === 'Approved' ? 'success' : 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('waste_type')->options(self::getWasteTypeOptions()),
                Tables\Filters\SelectFilter::make('status')->options(['Pending' => 'Pending', 'Approved' => 'Approved']),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (MaterialWastage $record) {
                        $record->approve();
                        Notification::make()->title('Wastage approved')->success()->send();
                    })
                    ->visible(fn (MaterialWastage $record) => $record->status === 'Pending'
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
            'index' => Pages\ListMaterialWastages::route('/'),
            'create' => Pages\CreateMaterialWastage::route('/create'),
            'edit' => Pages\EditMaterialWastage::route('/{record}/edit'),
        ];
    }
}
