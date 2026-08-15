<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SafetyInspectionResource\Pages;
use App\Models\SafetyInspection;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SafetyInspectionResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction', 'quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'safety.view';
    }

    protected static ?string $model = SafetyInspection::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Safety Inspections';

    protected static ?string $navigationGroup = 'Safety & Incident Management';

    protected static ?int $navigationSort = 60;

    public static function getResultOptions(): array
    {
        return ['Pass' => 'Pass', 'Pass with Observations' => 'Pass with Observations', 'Needs Review' => 'Needs Review', 'Fail' => 'Fail'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Inspection Details')->schema([
                    Forms\Components\TextInput::make('inspection_number')->label('Inspection #')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\Select::make('inspection_type')->label('Type')->options(array_combine(SafetyInspection::TYPES, SafetyInspection::TYPES))->default('Daily Site Inspection')->live()->required(),
                    Forms\Components\Select::make('project_id')->label('Project')->relationship('project', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('site_id')->label('Site')->relationship('site', 'name')->searchable()->preload()->required(),
                    Forms\Components\Select::make('equipment_id')->label('Equipment')->relationship('equipment', 'name')->searchable()->preload()
                        ->visible(fn (Forms\Get $get) => in_array($get('inspection_type'), ['Equipment Inspection', 'Vehicle Inspection', 'Crane Inspection'])),
                    Forms\Components\Select::make('inspector_id')->label('Inspector')->relationship('inspector', 'full_name')->searchable()->preload(),
                    Forms\Components\DatePicker::make('date')->default(now())->required(),
                    Forms\Components\Select::make('result')->options(self::getResultOptions())->default('Pass')->required(),
                    Forms\Components\TextInput::make('score')->numeric()->suffix('%'),
                    Forms\Components\Select::make('status')->options([
                        'Draft' => 'Draft', 'Submitted' => 'Submitted', 'Approved' => 'Approved',
                    ])->default('Draft')->required(),
                ])->columns(3),

                Forms\Components\Section::make('Checklist')->schema([
                    Forms\Components\Repeater::make('items')->relationship('items')->label('Checklist Items')->schema([
                        Forms\Components\TextInput::make('item_text')->label('Item')->required()->columnSpan(2),
                        Forms\Components\Select::make('status')->options(['Pass' => 'Pass', 'Fail' => 'Fail', 'N/A' => 'N/A'])->default('Pass')->required(),
                        Forms\Components\TextInput::make('notes'),
                    ])->columns(4)->defaultItems(0)->reorderableWithButtons()->columnSpanFull(),
                ])->collapsible(),

                Forms\Components\Section::make('Findings')->schema([
                    Forms\Components\Textarea::make('defects')->columnSpanFull()->rows(2),
                    Forms\Components\Textarea::make('recommendations')->columnSpanFull()->rows(2),
                    Forms\Components\FileUpload::make('photos')->label('Photos')->disk('public')->directory('safety-inspection-photos')->image()->multiple()->openable()->columnSpanFull(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('inspection_number')->label('Inspection #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('inspection_type')->label('Type')->toggleable(),
                Tables\Columns\TextColumn::make('site.name')->label('Site')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('date')->date()->sortable(),
                Tables\Columns\TextColumn::make('inspector.full_name')->label('Inspector')->toggleable(),
                Tables\Columns\TextColumn::make('result')->badge()->color(fn (string $state) => match ($state) {
                    'Pass' => 'success', 'Pass with Observations' => 'info', 'Needs Review' => 'warning', 'Fail' => 'danger', default => 'gray',
                }),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'Draft' => 'gray', 'Submitted' => 'warning', 'Approved' => 'success', default => 'gray',
                }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('inspection_type')->label('Type')->options(array_combine(SafetyInspection::TYPES, SafetyInspection::TYPES)),
                Tables\Filters\SelectFilter::make('result')->options(self::getResultOptions()),
                Tables\Filters\SelectFilter::make('status')->options([
                    'Draft' => 'Draft', 'Submitted' => 'Submitted', 'Approved' => 'Approved',
                ]),
                Tables\Filters\SelectFilter::make('site_id')->label('Site')->relationship('site', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->striped()
            ->defaultSort('date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSafetyInspections::route('/'),
            'create' => Pages\CreateSafetyInspection::route('/create'),
            'edit' => Pages\EditSafetyInspection::route('/{record}/edit'),
        ];
    }
}
