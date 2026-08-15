<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ToolboxTalkResource\Pages;
use App\Models\ToolboxTalk;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ToolboxTalkResource extends Resource
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

    protected static ?string $model = ToolboxTalk::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Toolbox Talks';

    protected static ?string $navigationGroup = 'Safety & Incident Management';

    protected static ?int $navigationSort = 70;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Toolbox Talk')->schema([
                    Forms\Components\TextInput::make('topic')->required()->columnSpan(2),
                    Forms\Components\Select::make('presenter_id')->label('Presenter')->relationship('presenter', 'full_name')->searchable()->preload(),
                    Forms\Components\Select::make('project_id')->label('Project')->relationship('project', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('site_id')->label('Site')->relationship('site', 'name')->searchable()->preload()->required(),
                    Forms\Components\DatePicker::make('date')->default(now())->required(),
                ])->columns(3),

                Forms\Components\Section::make('Content')->schema([
                    Forms\Components\Textarea::make('training_materials')->label('Training Materials / Key Points')->columnSpanFull()->rows(3),
                    Forms\Components\Textarea::make('comments')->columnSpanFull()->rows(2),
                    Forms\Components\FileUpload::make('photos')->label('Photos')->disk('public')->directory('toolbox-talk-photos')->image()->multiple()->openable()->columnSpanFull(),
                ]),

                Forms\Components\Section::make('Attendance Register')->schema([
                    Forms\Components\Repeater::make('attendees')->relationship('attendees')->schema([
                        Forms\Components\Select::make('staff_id')->label('Employee')->relationship('staff', 'full_name')->searchable()->preload(),
                        Forms\Components\TextInput::make('attendee_name')->label('Name (if not staff)'),
                        Forms\Components\Toggle::make('attended')->default(true),
                    ])->columns(3)->defaultItems(0)->columnSpanFull(),
                ])->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('attendees'))
            ->columns([
                Tables\Columns\TextColumn::make('topic')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('site.name')->label('Site')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('presenter.full_name')->label('Presenter')->toggleable(),
                Tables\Columns\TextColumn::make('date')->date()->sortable(),
                Tables\Columns\TextColumn::make('attendees_count')->label('Attendees')->badge()->color('info'),
            ])
            ->filters([
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
            'index' => Pages\ListToolboxTalks::route('/'),
            'create' => Pages\CreateToolboxTalk::route('/create'),
            'edit' => Pages\EditToolboxTalk::route('/{record}/edit'),
        ];
    }
}
