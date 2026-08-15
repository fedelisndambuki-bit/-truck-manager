<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SafetyMeetingResource\Pages;
use App\Models\SafetyMeeting;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SafetyMeetingResource extends Resource
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

    protected static ?string $model = SafetyMeeting::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Safety Meetings';

    protected static ?string $navigationGroup = 'Safety & Incident Management';

    protected static ?int $navigationSort = 80;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Safety Meeting')->schema([
                    Forms\Components\TextInput::make('title')->required()->columnSpan(2),
                    Forms\Components\Select::make('site_id')->label('Site')->relationship('site', 'name')->searchable()->preload(),
                    Forms\Components\DatePicker::make('date')->default(now())->required(),
                    Forms\Components\TextInput::make('attendees_count')->numeric()->default(0)->helperText('Auto-updates from the attendance register below when saved'),
                ])->columns(3),

                Forms\Components\Section::make('Agenda & Minutes')->schema([
                    Forms\Components\Textarea::make('agenda')->columnSpanFull()->rows(3),
                    Forms\Components\Textarea::make('minutes')->columnSpanFull()->rows(3),
                    Forms\Components\Textarea::make('notes')->columnSpanFull()->rows(2),
                    Forms\Components\FileUpload::make('attachments')->label('Attachments')->disk('public')->directory('safety-meeting-attachments')->multiple()->openable()->downloadable()->columnSpanFull(),
                ]),

                Forms\Components\Section::make('Attendees')->schema([
                    Forms\Components\Repeater::make('attendees')->relationship('attendees')->schema([
                        Forms\Components\Select::make('staff_id')->label('Employee')->relationship('staff', 'full_name')->searchable()->preload(),
                        Forms\Components\TextInput::make('attendee_name')->label('Name (if not staff)'),
                    ])->columns(2)->defaultItems(0)->columnSpanFull(),
                ])->collapsible(),

                Forms\Components\Section::make('Action Items')->schema([
                    Forms\Components\Repeater::make('actionItems')->relationship('actionItems')->schema([
                        Forms\Components\TextInput::make('description')->required()->columnSpan(2),
                        Forms\Components\Select::make('responsible_staff_id')->label('Responsible')->relationship('responsibleStaff', 'full_name')->searchable()->preload(),
                        Forms\Components\DatePicker::make('due_date'),
                        Forms\Components\Select::make('status')->options(['Open' => 'Open', 'In Progress' => 'In Progress', 'Done' => 'Done'])->default('Open'),
                    ])->columns(5)->defaultItems(0)->columnSpanFull(),
                ])->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\TextColumn::make('site.name')->label('Site')->placeholder('—'),
                Tables\Columns\TextColumn::make('date')->date()->sortable(),
                Tables\Columns\TextColumn::make('attendees_count')->label('Attendees'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSafetyMeetings::route('/'),
            'create' => Pages\CreateSafetyMeeting::route('/create'),
            'edit' => Pages\EditSafetyMeeting::route('/{record}/edit'),
        ];
    }
}
