<?php

namespace App\Filament\Clusters\ProjectFinance\Resources;

use App\Filament\Clusters\ProjectFinance;
use App\Filament\Clusters\ProjectFinance\Resources\CommitmentResource\Pages;
use App\Models\Commitment;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CommitmentResource extends Resource
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

    protected static ?string $model = Commitment::class;

    protected static ?string $cluster = ProjectFinance::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static ?string $navigationLabel = 'Commitments';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'commitment_number';

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            'Active' => 'success',
            'Closed' => 'gray',
            'Cancelled' => 'danger',
            default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        $currency = session('currency', 'KES');

        return $form
            ->schema([
                Forms\Components\Section::make('Commitment Details')->schema([
                    Forms\Components\Select::make('project_id')
                        ->relationship('project', 'name')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('type')->options([
                        'purchase_order' => 'Purchase Order', 'subcontract' => 'Subcontract',
                        'equipment_hire' => 'Equipment Hire', 'rental' => 'Rental Agreement',
                        'labour_contract' => 'Labour Contract',
                    ])->required()->live(),
                    Forms\Components\Select::make('purchase_order_id')
                        ->label('Purchase Order')
                        ->relationship('purchaseOrder', 'po_number')
                        ->searchable()->preload()
                        ->visible(fn (Forms\Get $get) => $get('type') === 'purchase_order'),
                    Forms\Components\Select::make('contract_id')
                        ->label('Subcontract')
                        ->relationship('contract', 'contract_number')
                        ->searchable()->preload()
                        ->visible(fn (Forms\Get $get) => $get('type') === 'subcontract'),
                    Forms\Components\TextInput::make('description'),
                    Forms\Components\Select::make('status')->options([
                        'Active' => 'Active', 'Closed' => 'Closed', 'Cancelled' => 'Cancelled',
                    ])->default('Active')->required(),
                ])->columns(2),
                Forms\Components\Section::make('Amounts')->schema([
                    Forms\Components\TextInput::make('committed_amount')->numeric()->prefix($currency)->required(),
                    Forms\Components\TextInput::make('invoiced_to_date')->numeric()->prefix($currency)->default(0),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $currency = session('currency', 'KES');

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('commitment_number')->label('Commitment #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('project.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->badge()->color('info')
                    ->formatStateUsing(fn (string $state) => str($state)->replace('_', ' ')->title()),
                Tables\Columns\TextColumn::make('committed_amount')->money($currency)->sortable(),
                Tables\Columns\TextColumn::make('invoiced_to_date')->money($currency),
                Tables\Columns\TextColumn::make('remaining')->money($currency),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => static::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')->label('Project')->relationship('project', 'name'),
                Tables\Filters\SelectFilter::make('status')->options([
                    'Active' => 'Active', 'Closed' => 'Closed', 'Cancelled' => 'Cancelled',
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
            'index' => Pages\ListCommitments::route('/'),
            'create' => Pages\CreateCommitment::route('/create'),
            'edit' => Pages\EditCommitment::route('/{record}/edit'),
        ];
    }
}
