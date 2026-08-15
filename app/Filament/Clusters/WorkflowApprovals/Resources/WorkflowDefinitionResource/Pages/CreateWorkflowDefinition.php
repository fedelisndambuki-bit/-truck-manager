<?php

namespace App\Filament\Clusters\WorkflowApprovals\Resources\WorkflowDefinitionResource\Pages;

use App\Filament\Clusters\WorkflowApprovals\Resources\WorkflowDefinitionResource;
use App\Models\Department;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateWorkflowDefinition extends CreateRecord
{
    protected static string $resource = WorkflowDefinitionResource::class;

    protected static bool $canCreateAnother = false;

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('New Workflow')
                ->description('Name it now — you\'ll design the approval flow on the next screen.')
                ->schema([
                    Forms\Components\TextInput::make('name')->required()->columnSpanFull(),
                    Forms\Components\Textarea::make('description')->columnSpanFull(),
                    Forms\Components\Select::make('module')->options([
                        'Core' => 'Core', 'Construction' => 'Construction', 'Fleet' => 'Fleet',
                        'HR' => 'HR', 'Finance' => 'Finance', 'Quarry' => 'Quarry',
                    ])->default('Core')->required(),
                    Forms\Components\TextInput::make('entity_type')
                        ->label('Applies To (model, optional)')
                        ->helperText('e.g. App\Models\LeaveRequest'),
                    Forms\Components\Select::make('department_id')
                        ->label('Department (optional)')
                        ->options(fn () => Department::query()->pluck('name', 'id')),
                    Forms\Components\Select::make('industry')
                        ->label('Industry (optional — blank = all)')
                        ->options([
                            'construction' => 'Construction', 'trucking' => 'Trucking', 'quarry' => 'Quarry',
                        ]),
                ])->columns(2),
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = Auth::user()?->company_id;
        $data['status'] = 'Draft';
        $data['version'] = 1;
        $data['created_by_id'] = Auth::id();
        $data['definition'] = [
            'nodes' => [
                ['id' => '1', 'type' => 'start', 'position' => ['x' => 60, 'y' => 200], 'config' => []],
            ],
            'edges' => [],
        ];
        $data['node_types_used'] = ['start'];

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return \App\Filament\Clusters\WorkflowApprovals\Resources\WorkflowDefinitionResource\Pages\DesignWorkflow::getUrl(['record' => $this->record]);
    }
}
