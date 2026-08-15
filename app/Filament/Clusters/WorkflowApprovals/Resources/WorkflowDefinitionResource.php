<?php

namespace App\Filament\Clusters\WorkflowApprovals\Resources;

use App\Filament\Clusters\WorkflowApprovals;
use App\Filament\Clusters\WorkflowApprovals\Resources\WorkflowDefinitionResource\Pages;
use App\Models\WorkflowDefinition;
use App\Traits\HasModuleAccess;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class WorkflowDefinitionResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'workflow.manage';
    }

    protected static ?string $model = WorkflowDefinition::class;

    protected static ?string $cluster = WorkflowApprovals::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';

    protected static ?string $navigationLabel = 'Workflow Designer';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        $companyId = Auth::user()?->company_id;

        return $table
            ->modifyQueryUsing(fn ($query) => $query->withoutGlobalScope('company')->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->orWhereNull('company_id');
            }))
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable()->weight('semibold'),
                Tables\Columns\IconColumn::make('company_id')->label('Template')->boolean(true)->getStateUsing(fn (WorkflowDefinition $record) => $record->isTemplate()),
                Tables\Columns\TextColumn::make('module')->badge()->color('info'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'Published' => 'success', 'Archived' => 'gray', default => 'warning',
                }),
                Tables\Columns\TextColumn::make('version')->label('v')->sortable(),
                Tables\Columns\TextColumn::make('department.name')->label('Department')->toggleable(),
                Tables\Columns\TextColumn::make('industry')->placeholder('All industries')->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('module')->options([
                    'Core' => 'Core', 'Construction' => 'Construction', 'Fleet' => 'Fleet',
                    'HR' => 'HR', 'Finance' => 'Finance', 'Quarry' => 'Quarry',
                ]),
                Tables\Filters\SelectFilter::make('status')->options([
                    'Draft' => 'Draft', 'Published' => 'Published', 'Archived' => 'Archived',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('design')
                    ->label('Design')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->url(fn (WorkflowDefinition $record) => Pages\DesignWorkflow::getUrl(['record' => $record])),
                Tables\Actions\DeleteAction::make()->visible(fn (WorkflowDefinition $record) => ! $record->isTemplate()),
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
            'index' => Pages\ListWorkflowDefinitions::route('/'),
            'create' => Pages\CreateWorkflowDefinition::route('/create'),
            'edit' => Pages\DesignWorkflow::route('/{record}/edit'),
        ];
    }
}
