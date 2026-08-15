<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NearMissResource\Pages;
use App\Models\NearMiss;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class NearMissResource extends Resource
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

    protected static ?string $model = NearMiss::class;

    protected static ?string $navigationIcon = 'heroicon-o-eye';

    protected static ?string $navigationLabel = 'Near Misses';

    protected static ?string $navigationGroup = 'Safety & Incident Management';

    protected static ?int $navigationSort = 30;

    public static function getRiskLevelOptions(): array
    {
        return ['Low' => 'Low', 'Medium' => 'Medium', 'High' => 'High', 'Critical' => 'Critical'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Near Miss Details')->schema([
                    Forms\Components\TextInput::make('near_miss_number')->label('Near Miss #')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\DatePicker::make('date')->required()->default(now()),
                    Forms\Components\Select::make('project_id')->label('Project')->relationship('project', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('site_id')->label('Site')->relationship('site', 'name')->searchable()->preload()->required(),
                    Forms\Components\Select::make('reported_by_id')->label('Reported By')->relationship('reportedBy', 'full_name')->searchable()->preload(),
                    Forms\Components\Select::make('risk_level')->options(self::getRiskLevelOptions())->default('Medium')->required(),
                    Forms\Components\Select::make('status')->options([
                        'Open' => 'Open', 'Under Review' => 'Under Review', 'Closed' => 'Closed',
                    ])->default('Open')->required(),
                ])->columns(3),

                Forms\Components\Section::make('What Could Have Happened')->schema([
                    Forms\Components\Textarea::make('description')->required()->columnSpanFull()->rows(3),
                    Forms\Components\Textarea::make('potential_consequence')->label('Potential Consequence')->columnSpanFull()->rows(2),
                    Forms\Components\Textarea::make('corrective_action')->label('Corrective Action Taken')->columnSpanFull()->rows(2),
                ]),

                Forms\Components\Section::make('Photos')->schema([
                    Forms\Components\FileUpload::make('photos')->label('Photos')->disk('public')->directory('near-miss-photos')->image()->multiple()->openable()->columnSpanFull(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('near_miss_number')->label('Near Miss #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('site.name')->label('Site')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('date')->date()->sortable(),
                Tables\Columns\TextColumn::make('description')->limit(50),
                Tables\Columns\TextColumn::make('risk_level')->badge()->color(fn (string $state) => match ($state) {
                    'Low' => 'success', 'Medium' => 'warning', 'High', 'Critical' => 'danger', default => 'gray',
                }),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'Open' => 'danger', 'Under Review' => 'warning', 'Closed' => 'success', default => 'gray',
                }),
                Tables\Columns\TextColumn::make('reportedBy.full_name')->label('Reported By')->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('risk_level')->options(self::getRiskLevelOptions()),
                Tables\Filters\SelectFilter::make('status')->options([
                    'Open' => 'Open', 'Under Review' => 'Under Review', 'Closed' => 'Closed',
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
            'index' => Pages\ListNearMisses::route('/'),
            'create' => Pages\CreateNearMiss::route('/create'),
            'edit' => Pages\EditNearMiss::route('/{record}/edit'),
        ];
    }
}
