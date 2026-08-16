<?php

namespace App\Filament\Clusters\BlastManagement\Resources;

use App\Filament\Clusters\BlastManagement;
use App\Filament\Clusters\BlastManagement\Resources\BlastComplianceResource\Pages;
use App\Models\BlastCompliance;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BlastComplianceResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = BlastManagement::class;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'blast.view';
    }

    protected static ?string $model = BlastCompliance::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $navigationLabel = 'Compliance';

    protected static ?int $navigationSort = 11;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['blastPlan']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Compliance')->schema([
                    Forms\Components\Select::make('blast_plan_id')->label('Blast Plan')
                        ->relationship('blastPlan', 'blast_number')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->blast_number ?? ('Blast #' . $record->id))
                        ->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('permit_number'),
                    Forms\Components\DatePicker::make('permit_expiry'),
                    Forms\Components\Toggle::make('regulatory_approval'),
                    Forms\Components\Toggle::make('blast_notifications_sent'),
                    Forms\Components\Toggle::make('inspector_approval'),
                    Forms\Components\Toggle::make('safety_checklist_complete'),
                    Forms\Components\Toggle::make('environmental_checklist_complete'),
                    Forms\Components\Toggle::make('legal_compliance'),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('blastPlan.blast_number')->label('Blast #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('permit_number')->placeholder('—'),
                Tables\Columns\TextColumn::make('permit_expiry')->date()->placeholder('—')
                    ->color(fn (BlastCompliance $record): string => $record->permit_expiry && $record->permit_expiry->isPast() ? 'danger' : 'gray'),
                Tables\Columns\TextColumn::make('score')
                    ->label('Compliance Score')
                    ->state(fn (BlastCompliance $record): string => $record->scorePercent() . '%')
                    ->badge()
                    ->color(fn (BlastCompliance $record): string => $record->scorePercent() >= 80 ? 'success' : ($record->scorePercent() >= 50 ? 'warning' : 'danger')),
            ])
            ->defaultSort('created_at', 'desc')
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
            'index' => Pages\ListBlastCompliances::route('/'),
            'create' => Pages\CreateBlastCompliance::route('/create'),
            'edit' => Pages\EditBlastCompliance::route('/{record}/edit'),
        ];
    }
}
