<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmergencyItemResource\Pages;
use App\Models\EmergencyItem;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EmergencyItemResource extends Resource
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

    protected static ?string $model = EmergencyItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationLabel = 'Emergency Management';

    protected static ?string $navigationGroup = 'Safety & Incident Management';

    protected static ?int $navigationSort = 100;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['site', 'responsibleStaff']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Emergency Preparedness Register')->schema([
                    Forms\Components\Select::make('type')->options(array_combine(EmergencyItem::TYPES, EmergencyItem::TYPES))->live()->required(),
                    Forms\Components\TextInput::make('name')->required()->columnSpan(2)
                        ->helperText(fn (Forms\Get $get) => match ($get('type')) {
                            'Emergency Contact' => 'e.g. Nairobi Fire Brigade, St. Mary\'s Hospital',
                            'Response Plan' => 'e.g. Fire Evacuation Plan - Block A',
                            'Assembly Point' => 'e.g. Main Gate Assembly Point',
                            'Fire Equipment' => 'e.g. Extinguisher FE-014, Block B Corridor',
                            'First Aid Kit' => 'e.g. First Aid Kit - Site Office',
                            'Drill' => 'e.g. Q3 Fire Evacuation Drill',
                            'Team Member' => 'e.g. Fire Warden - Block A',
                            default => null,
                        }),
                    Forms\Components\Select::make('project_id')->label('Project')->relationship('project', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('site_id')->label('Site')->relationship('site', 'name')->searchable()->preload(),
                    Forms\Components\TextInput::make('location'),
                    Forms\Components\Select::make('responsible_staff_id')->label('Responsible Person')->relationship('responsibleStaff', 'full_name')->searchable()->preload(),
                    Forms\Components\Select::make('status')->options([
                        'Active' => 'Active', 'Needs Attention' => 'Needs Attention', 'Inactive' => 'Inactive',
                    ])->default('Active')->required(),
                    Forms\Components\DatePicker::make('last_checked_at')->label('Last Checked / Conducted'),
                    Forms\Components\DatePicker::make('next_check_date')->label('Next Check / Due Date'),
                ])->columns(3),

                Forms\Components\Section::make('Additional Details')->schema([
                    Forms\Components\KeyValue::make('details')
                        ->keyLabel('Field')
                        ->valueLabel('Value')
                        ->helperText('e.g. Phone Number, Capacity, Contents, Scenario, Participants, Duration — whatever applies to this item type')
                        ->columnSpanFull(),
                ])->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')->badge()->color(fn (string $state) => match ($state) {
                    'Emergency Contact' => 'info', 'Response Plan' => 'gray', 'Assembly Point' => 'success',
                    'Fire Equipment' => 'danger', 'First Aid Kit' => 'warning', 'Drill' => 'info', 'Team Member' => 'gray',
                    default => 'gray',
                }),
                Tables\Columns\TextColumn::make('name')->searchable()->limit(35),
                Tables\Columns\TextColumn::make('site.name')->label('Site')->toggleable()->placeholder('—'),
                Tables\Columns\TextColumn::make('responsibleStaff.full_name')->label('Responsible')->toggleable(),
                Tables\Columns\TextColumn::make('next_check_date')->label('Next Check')->date()->sortable()->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'Active' => 'success', 'Needs Attention' => 'warning', 'Inactive' => 'gray', default => 'gray',
                }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->options(array_combine(EmergencyItem::TYPES, EmergencyItem::TYPES)),
                Tables\Filters\SelectFilter::make('status')->options([
                    'Active' => 'Active', 'Needs Attention' => 'Needs Attention', 'Inactive' => 'Inactive',
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
            ->defaultSort('type');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmergencyItems::route('/'),
            'create' => Pages\CreateEmergencyItem::route('/create'),
            'edit' => Pages\EditEmergencyItem::route('/{record}/edit'),
        ];
    }
}
