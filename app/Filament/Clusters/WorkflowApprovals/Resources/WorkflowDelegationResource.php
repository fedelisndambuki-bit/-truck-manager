<?php

namespace App\Filament\Clusters\WorkflowApprovals\Resources;

use App\Filament\Clusters\WorkflowApprovals;
use App\Filament\Clusters\WorkflowApprovals\Resources\WorkflowDelegationResource\Pages;
use App\Models\User;
use App\Models\WorkflowDelegation;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class WorkflowDelegationResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'workflow.view';
    }

    protected static ?string $model = WorkflowDelegation::class;

    protected static ?string $cluster = WorkflowApprovals::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Delegations';

    protected static ?int $navigationSort = 8;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['delegator', 'delegate', 'workflowDefinition']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Delegation')->schema([
                Forms\Components\Select::make('delegator_user_id')
                    ->label('Delegator (from)')
                    ->options(fn () => User::query()->when(Auth::user()?->company_id, fn ($q) => $q->where('company_id', Auth::user()->company_id))->pluck('name', 'id'))
                    ->default(fn () => Auth::id())
                    ->searchable()->required(),
                Forms\Components\Select::make('delegate_user_id')
                    ->label('Delegate (to)')
                    ->options(fn () => User::query()->when(Auth::user()?->company_id, fn ($q) => $q->where('company_id', Auth::user()->company_id))->pluck('name', 'id'))
                    ->searchable()->required(),
                Forms\Components\Select::make('scope')->options([
                    'all' => 'All Workflows', 'workflow_definition' => 'Specific Workflow',
                ])->default('all')->live()->required(),
                Forms\Components\Select::make('workflow_definition_id')
                    ->label('Workflow')
                    ->relationship('workflowDefinition', 'name')
                    ->visible(fn (Forms\Get $get) => $get('scope') === 'workflow_definition')
                    ->required(fn (Forms\Get $get) => $get('scope') === 'workflow_definition'),
                Forms\Components\DatePicker::make('start_date')->required()->default(now()),
                Forms\Components\DatePicker::make('end_date')->required()->default(now()->addWeek()),
                Forms\Components\Textarea::make('reason')->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('delegator.name')->label('From')->searchable(),
                Tables\Columns\TextColumn::make('delegate.name')->label('To')->searchable(),
                Tables\Columns\TextColumn::make('scope')->badge()->color('info'),
                Tables\Columns\TextColumn::make('workflowDefinition.name')->label('Workflow')->placeholder('All')->toggleable(),
                Tables\Columns\TextColumn::make('start_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('end_date')->date()->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListWorkflowDelegations::route('/'),
            'create' => Pages\CreateWorkflowDelegation::route('/create'),
            'edit' => Pages\EditWorkflowDelegation::route('/{record}/edit'),
        ];
    }
}
