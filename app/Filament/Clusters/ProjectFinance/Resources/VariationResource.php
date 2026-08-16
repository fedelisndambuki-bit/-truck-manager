<?php

namespace App\Filament\Clusters\ProjectFinance\Resources;

use App\Filament\Clusters\ProjectFinance;
use App\Filament\Clusters\ProjectFinance\Resources\VariationResource\Pages;
use App\Models\ChangeOrder;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * Client-facing variations. Backed by the same `change_orders` table as
 * ChangeOrderResource (type-scoped to `client_variation`) rather than a
 * separate table — see ProjectFinance module plan for the reasoning.
 */
class VariationResource extends Resource
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

    protected static ?string $model = ChangeOrder::class;

    protected static ?string $cluster = ProjectFinance::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationLabel = 'Variations';

    protected static ?int $navigationSort = 8;

    protected static ?string $recordTitleAttribute = 'co_number';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('type', 'client_variation')->with(['project']);
    }

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            'Approved' => 'success',
            'Submitted', 'Under Review' => 'warning',
            'Rejected' => 'danger',
            default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        $currency = session('currency', 'KES');

        return $form
            ->schema([
                Forms\Components\Hidden::make('type')->default('client_variation'),
                Forms\Components\Section::make('Variation Details')->schema([
                    Forms\Components\Select::make('project_id')
                        ->relationship('project', 'name')
                        ->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('title')->label('Variation Title')->required(),
                    Forms\Components\Select::make('status')->options([
                        'Draft' => 'Draft', 'Submitted' => 'Submitted', 'Under Review' => 'Under Review',
                        'Approved' => 'Approved', 'Rejected' => 'Rejected',
                    ])->default('Draft')->required(),
                    Forms\Components\Select::make('requested_by_id')
                        ->label('Requested By')
                        ->relationship('requestedBy', 'full_name')
                        ->searchable()->preload(),
                    Forms\Components\Textarea::make('reason')->columnSpanFull(),
                    Forms\Components\FileUpload::make('supporting_document_path')
                        ->label('Supporting Documents')
                        ->directory('project-finance/variations')
                        ->columnSpanFull(),
                ])->columns(2),
                Forms\Components\Section::make('Impact')->schema([
                    Forms\Components\TextInput::make('cost_impact')
                        ->numeric()->prefix($currency)
                        ->helperText('Positive = cost increase, negative = cost reduction.'),
                    Forms\Components\TextInput::make('time_impact_days')->numeric()->default(0)->suffix('days')->label('Time Extension'),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $currency = session('currency', 'KES');

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('co_number')->label('Variation #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('project.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('title')->limit(35)->searchable(),
                Tables\Columns\TextColumn::make('cost_impact')->label('Cost Increase/Reduction')->money($currency)->sortable()
                    ->color(fn (ChangeOrder $record) => $record->cost_impact >= 0 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('time_impact_days')->suffix(' days')->label('Time Extension'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => static::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')->label('Project')->relationship('project', 'name'),
                Tables\Filters\SelectFilter::make('status')->options([
                    'Draft' => 'Draft', 'Submitted' => 'Submitted', 'Under Review' => 'Under Review',
                    'Approved' => 'Approved', 'Rejected' => 'Rejected',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ChangeOrder $record) => in_array($record->status, ['Submitted', 'Under Review']) && Auth::user()?->can('project_finance.approve'))
                    ->requiresConfirmation()
                    ->action(fn (ChangeOrder $record) => $record->approve()),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (ChangeOrder $record) => in_array($record->status, ['Submitted', 'Under Review']) && Auth::user()?->can('project_finance.approve'))
                    ->requiresConfirmation()
                    ->action(fn (ChangeOrder $record) => $record->reject()),
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
            'index' => Pages\ListVariations::route('/'),
            'create' => Pages\CreateVariation::route('/create'),
            'edit' => Pages\EditVariation::route('/{record}/edit'),
        ];
    }
}
