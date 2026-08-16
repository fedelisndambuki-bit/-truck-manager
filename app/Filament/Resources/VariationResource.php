<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VariationResource\Pages;
use App\Models\Variation;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VariationResource extends Resource
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

    protected static ?string $model = Variation::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationLabel = 'Variations';

    protected static ?string $navigationGroup = 'Subcontractor Management';

    protected static ?int $navigationSort = 11;

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            'Approved' => 'success',
            'Under Review' => 'warning',
            'Rejected' => 'danger',
            default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Variation Details')->schema([
                    Forms\Components\Select::make('contract_id')
                        ->label('Contract')
                        ->relationship('contract', 'contract_number')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('status')
                        ->options([
                            'Requested' => 'Requested', 'Under Review' => 'Under Review',
                            'Approved' => 'Approved', 'Rejected' => 'Rejected',
                        ])->default('Requested')->required(),
                    Forms\Components\TextInput::make('cost_impact')
                        ->numeric()->prefix(session('currency', 'KES'))
                        ->helperText('Positive to increase contract value, negative to decrease.'),
                    Forms\Components\TextInput::make('time_extension_days')->numeric()->default(0)->suffix('days'),
                    Forms\Components\Select::make('requested_by_id')
                        ->label('Requested By')
                        ->relationship('requestedBy', 'full_name')
                        ->searchable()->preload(),
                    Forms\Components\Textarea::make('reason')->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('variation_number')->label('Variation #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('contract.contract_number')->label('Contract')->searchable(),
                Tables\Columns\TextColumn::make('contract.subcontractor.company_name')->label('Subcontractor')->searchable(),
                Tables\Columns\TextColumn::make('cost_impact')->money(session('currency', 'KES'))->sortable(),
                Tables\Columns\TextColumn::make('time_extension_days')->suffix(' days')->label('Time Extension'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => static::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'Requested' => 'Requested', 'Under Review' => 'Under Review',
                    'Approved' => 'Approved', 'Rejected' => 'Rejected',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Variation $record) => in_array($record->status, ['Requested', 'Under Review']))
                    ->requiresConfirmation()
                    ->action(fn (Variation $record) => $record->approve()),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Variation $record) => in_array($record->status, ['Requested', 'Under Review']))
                    ->requiresConfirmation()
                    ->action(fn (Variation $record) => $record->reject()),
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
            'index' => Pages\ListVariations::route('/'),
            'create' => Pages\CreateVariation::route('/create'),
            'edit' => Pages\EditVariation::route('/{record}/edit'),
        ];
    }
}
