<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkOrderResource\Pages;
use App\Models\WorkOrder;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WorkOrderResource extends Resource
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

    protected static ?string $model = WorkOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Work Orders';

    protected static ?string $navigationGroup = 'Subcontractor Management';

    protected static ?int $navigationSort = 10;

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            'Completed' => 'success',
            'In Progress' => 'info',
            'Cancelled' => 'danger',
            default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Work Order Details')->schema([
                    Forms\Components\Select::make('subcontractor_id')
                        ->label('Subcontractor')
                        ->relationship('subcontractor', 'company_name')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('contract_id')
                        ->label('Contract')
                        ->relationship('contract', 'contract_number')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('project_id')
                        ->label('Project')
                        ->relationship('project', 'name')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('site_id')
                        ->label('Site')
                        ->relationship('site', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('priority')
                        ->options(['Low' => 'Low', 'Normal' => 'Normal', 'High' => 'High', 'Urgent' => 'Urgent'])
                        ->default('Normal')->required(),
                    Forms\Components\Select::make('status')
                        ->options([
                            'Pending' => 'Pending', 'In Progress' => 'In Progress',
                            'Completed' => 'Completed', 'Cancelled' => 'Cancelled',
                        ])->default('Pending')->required(),
                    Forms\Components\DatePicker::make('expected_completion'),
                    Forms\Components\Select::make('supervisor_id')
                        ->label('Supervisor')
                        ->relationship('supervisor', 'full_name')
                        ->searchable()->preload(),
                    Forms\Components\Textarea::make('description')->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('work_order_number')->label('WO #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('subcontractor.company_name')->label('Subcontractor')->searchable(),
                Tables\Columns\TextColumn::make('project.name')->label('Project')->searchable(),
                Tables\Columns\TextColumn::make('priority')->badge()->color(fn (string $state) => match ($state) {
                    'Urgent' => 'danger', 'High' => 'warning', 'Low' => 'gray', default => 'info',
                }),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => static::getStatusColor($state)),
                Tables\Columns\TextColumn::make('expected_completion')->date()->placeholder('—')->sortable(),
                Tables\Columns\TextColumn::make('supervisor.full_name')->label('Supervisor')->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'Pending' => 'Pending', 'In Progress' => 'In Progress',
                    'Completed' => 'Completed', 'Cancelled' => 'Cancelled',
                ]),
                Tables\Filters\SelectFilter::make('priority')->options([
                    'Low' => 'Low', 'Normal' => 'Normal', 'High' => 'High', 'Urgent' => 'Urgent',
                ]),
                Tables\Filters\SelectFilter::make('project_id')->label('Project')->relationship('project', 'name'),
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
            'index' => Pages\ListWorkOrders::route('/'),
            'create' => Pages\CreateWorkOrder::route('/create'),
            'edit' => Pages\EditWorkOrder::route('/{record}/edit'),
        ];
    }
}
