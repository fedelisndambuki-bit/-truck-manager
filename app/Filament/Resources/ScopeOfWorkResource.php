<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScopeOfWorkResource\Pages;
use App\Models\ScopeOfWork;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ScopeOfWorkResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'subcontractors.view';
    }

    protected static ?string $model = ScopeOfWork::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?string $navigationLabel = 'Scope of Work';

    protected static ?string $navigationGroup = 'Subcontractor Management';

    protected static ?int $navigationSort = 8;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Scope Details')->schema([
                    Forms\Components\Select::make('contract_id')
                        ->label('Contract')
                        ->relationship('contract', 'contract_number')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('milestone_id')
                        ->label('Linked Milestone')
                        ->relationship('milestone', 'name')
                        ->searchable()->preload(),
                    Forms\Components\DatePicker::make('expected_completion'),
                    Forms\Components\Textarea::make('acceptance_criteria')->columnSpanFull(),
                ])->columns(2),
                Forms\Components\Section::make('Deliverables & BOQ')->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship('items')
                        ->schema([
                            Forms\Components\TextInput::make('deliverable')->required()->columnSpan(2),
                            Forms\Components\TextInput::make('activity'),
                            Forms\Components\TextInput::make('boq_reference')->label('BOQ Ref'),
                            Forms\Components\TextInput::make('quantity')->numeric()->default(1),
                            Forms\Components\TextInput::make('unit'),
                            Forms\Components\TextInput::make('unit_rate')->numeric()->prefix(session('currency', 'KES')),
                            Forms\Components\Select::make('status')->options([
                                'Pending' => 'Pending', 'In Progress' => 'In Progress', 'Completed' => 'Completed',
                            ])->default('Pending'),
                        ])
                        ->columns(4)
                        ->columnSpanFull()
                        ->defaultItems(1)
                        ->addActionLabel('Add Deliverable'),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('contract.contract_number')->label('Contract')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('contract.subcontractor.company_name')->label('Subcontractor')->searchable(),
                Tables\Columns\TextColumn::make('items_count')->counts('items')->label('Line Items'),
                Tables\Columns\TextColumn::make('total_value')->label('Total Value')->money(session('currency', 'KES'))
                    ->state(fn (ScopeOfWork $record) => $record->total_value),
                Tables\Columns\TextColumn::make('percent_complete')->label('% Complete')->suffix('%')
                    ->state(fn (ScopeOfWork $record) => $record->percent_complete),
                Tables\Columns\TextColumn::make('expected_completion')->date()->placeholder('—')->sortable(),
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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['contract.subcontractor']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListScopeOfWorks::route('/'),
            'create' => Pages\CreateScopeOfWork::route('/create'),
            'edit' => Pages\EditScopeOfWork::route('/{record}/edit'),
        ];
    }
}
