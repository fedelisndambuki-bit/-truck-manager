<?php

namespace App\Filament\Clusters\CrmSales\Resources;

use App\Filament\Clusters\CrmSales;
use App\Filament\Clusters\CrmSales\Resources\OpportunityResource\Pages;
use App\Models\Opportunity;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OpportunityResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'crm.view';
    }

    protected static ?string $model = Opportunity::class;

    protected static ?string $cluster = CrmSales::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Opportunities';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'opportunity_number';

    public static function getStageOptions(): array
    {
        return array_combine(
            $stages = ['Qualification', 'Proposal', 'Negotiation', 'Verbal Commitment', 'Won', 'Lost'],
            $stages
        );
    }

    public static function getStageColor(string $stage): string
    {
        return match ($stage) {
            'Won' => 'success',
            'Lost' => 'danger',
            'Negotiation', 'Verbal Commitment' => 'warning',
            'Proposal' => 'info',
            default => 'gray',
        };
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['client', 'assignedTo']);
    }

    public static function form(Form $form): Form
    {
        $currency = session('currency', 'KES');

        return $form
            ->schema([
                Forms\Components\Section::make('Opportunity Details')->schema([
                    Forms\Components\Select::make('client_id')
                        ->label('Client')
                        ->relationship('client', 'company_name')
                        ->searchable()->preload()->required()->live(),
                    Forms\Components\Select::make('contact_id')
                        ->label('Contact')
                        ->relationship('contact', 'full_name', fn (Forms\Get $get, $query) => $get('client_id')
                            ? $query->where('client_id', $get('client_id'))
                            : $query)
                        ->searchable()->preload(),
                    Forms\Components\Select::make('material_id')
                        ->label('Product')
                        ->relationship('material', 'name')
                        ->searchable()->preload(),
                    Forms\Components\TextInput::make('estimated_quantity')->numeric(),
                    Forms\Components\TextInput::make('estimated_revenue')->numeric()->prefix($currency),
                    Forms\Components\TextInput::make('probability')->numeric()->minValue(0)->maxValue(100)->suffix('%')->default(50),
                    Forms\Components\DatePicker::make('expected_close_date'),
                    Forms\Components\Select::make('stage')->options(self::getStageOptions())->default('Qualification')->required(),
                    Forms\Components\Select::make('assigned_to_id')
                        ->label('Assigned To')
                        ->relationship('assignedTo', 'full_name')
                        ->searchable()->preload(),
                    Forms\Components\Textarea::make('competitors')->columnSpanFull(),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        $currency = session('currency', 'KES');

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('opportunity_number')->label('Opportunity #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('client.company_name')->label('Client')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('stage')->badge()->color(fn (string $state) => static::getStageColor($state)),
                Tables\Columns\TextColumn::make('probability')->suffix('%'),
                Tables\Columns\TextColumn::make('estimated_revenue')->money($currency)->sortable(),
                Tables\Columns\TextColumn::make('expected_close_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('assignedTo.full_name')->label('Assigned To')->placeholder('Unassigned'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('stage')->options(self::getStageOptions()),
                Tables\Filters\SelectFilter::make('client_id')->relationship('client', 'company_name'),
            ])
            ->actions([
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
            'index' => Pages\ListOpportunities::route('/'),
            'create' => Pages\CreateOpportunity::route('/create'),
            'edit' => Pages\EditOpportunity::route('/{record}/edit'),
        ];
    }
}
