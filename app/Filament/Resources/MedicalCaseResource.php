<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MedicalCaseResource\Pages;
use App\Models\MedicalCase;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MedicalCaseResource extends Resource
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

    protected static ?string $model = MedicalCase::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationLabel = 'Medical Cases';

    protected static ?string $navigationGroup = 'Safety & Incident Management';

    protected static ?int $navigationSort = 120;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Medical Case')->schema([
                    Forms\Components\TextInput::make('case_number')->label('Case #')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\Select::make('staff_id')->label('Employee')->relationship('staff', 'full_name')->searchable()->preload()->required(),
                    Forms\Components\Select::make('safety_incident_id')->label('Related Incident')->relationship('safetyIncident', 'incident_number')->searchable()->preload(),
                    Forms\Components\DatePicker::make('visit_date')->default(now())->required(),
                    Forms\Components\TextInput::make('hospital'),
                    Forms\Components\TextInput::make('doctor'),
                ])->columns(3),

                Forms\Components\Section::make('Treatment')->schema([
                    Forms\Components\Textarea::make('diagnosis')->columnSpanFull()->rows(2),
                    Forms\Components\Textarea::make('treatment')->columnSpanFull()->rows(2),
                    Forms\Components\Textarea::make('medical_restrictions')->columnSpanFull()->rows(2),
                ]),

                Forms\Components\Section::make('Return to Work')->schema([
                    Forms\Components\DatePicker::make('return_to_work_date'),
                    Forms\Components\Select::make('medical_clearance')->options([
                        'Pending' => 'Pending', 'Cleared' => 'Cleared', 'Restricted' => 'Restricted', 'Not Cleared' => 'Not Cleared',
                    ])->default('Pending')->required(),
                    Forms\Components\Select::make('status')->options([
                        'Open' => 'Open', 'Under Treatment' => 'Under Treatment', 'Closed' => 'Closed',
                    ])->default('Open')->required(),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('case_number')->label('Case #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('staff.full_name')->label('Employee')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('visit_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('hospital')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('return_to_work_date')->label('Return to Work')->date()->placeholder('—'),
                Tables\Columns\TextColumn::make('medical_clearance')->badge()->color(fn (string $state) => match ($state) {
                    'Cleared' => 'success', 'Pending' => 'warning', 'Restricted' => 'info', 'Not Cleared' => 'danger', default => 'gray',
                }),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'Open' => 'danger', 'Under Treatment' => 'warning', 'Closed' => 'success', default => 'gray',
                }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('medical_clearance')->options([
                    'Pending' => 'Pending', 'Cleared' => 'Cleared', 'Restricted' => 'Restricted', 'Not Cleared' => 'Not Cleared',
                ]),
                Tables\Filters\SelectFilter::make('status')->options([
                    'Open' => 'Open', 'Under Treatment' => 'Under Treatment', 'Closed' => 'Closed',
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
            ->striped()
            ->defaultSort('visit_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMedicalCases::route('/'),
            'create' => Pages\CreateMedicalCase::route('/create'),
            'edit' => Pages\EditMedicalCase::route('/{record}/edit'),
        ];
    }
}
