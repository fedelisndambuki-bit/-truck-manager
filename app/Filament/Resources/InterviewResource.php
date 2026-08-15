<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InterviewResource\Pages;
use App\Models\Interview;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InterviewResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction', 'quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'hr.recruitment_access';
    }

    protected static ?string $model = Interview::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Labour & HR Management';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Interview')->schema([
                    Forms\Components\Select::make('application_id')
                        ->label('Application')
                        ->relationship('application', 'candidate_name')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('interviewer_id')
                        ->label('Interviewer')
                        ->relationship('interviewer', 'full_name')
                        ->searchable()->preload(),
                    Forms\Components\DateTimePicker::make('scheduled_at'),
                    Forms\Components\Select::make('result')->options([
                        'Pending' => 'Pending', 'Passed' => 'Passed', 'Failed' => 'Failed',
                    ])->default('Pending')->required(),
                    Forms\Components\Textarea::make('notes')->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('application.candidate_name')->label('Candidate')->searchable(),
                Tables\Columns\TextColumn::make('interviewer.full_name')->label('Interviewer')->placeholder('—'),
                Tables\Columns\TextColumn::make('scheduled_at')->dateTime()->placeholder('—'),
                Tables\Columns\TextColumn::make('result')->badge()->color(fn (string $state) => match ($state) {
                    'Passed' => 'success',
                    'Failed' => 'danger',
                    default => 'warning',
                }),
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
            'index' => Pages\ListInterviews::route('/'),
            'create' => Pages\CreateInterview::route('/create'),
            'edit' => Pages\EditInterview::route('/{record}/edit'),
        ];
    }
}
