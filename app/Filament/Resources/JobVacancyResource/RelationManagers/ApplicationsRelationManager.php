<?php

namespace App\Filament\Resources\JobVacancyResource\RelationManagers;

use App\Models\JobApplication;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ApplicationsRelationManager extends RelationManager
{
    protected static string $relationship = 'applications';

    protected static ?string $title = 'Applications';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('candidate_name')->required(),
            Forms\Components\TextInput::make('email')->email(),
            Forms\Components\TextInput::make('phone'),
            Forms\Components\FileUpload::make('resume_path')
                ->label('Resume')
                ->disk('public')
                ->directory('recruitment-resumes')
                ->openable()
                ->downloadable(),
            Forms\Components\Select::make('status')
                ->options([
                    'Applied' => 'Applied', 'Shortlisted' => 'Shortlisted', 'Interview' => 'Interview',
                    'Offer' => 'Offer', 'Hired' => 'Hired', 'Rejected' => 'Rejected',
                ])
                ->default('Applied')
                ->required(),
            Forms\Components\Select::make('background_check_status')
                ->options(['Pending' => 'Pending', 'Passed' => 'Passed', 'Failed' => 'Failed']),
            Forms\Components\Textarea::make('notes')->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('candidate_name')
            ->columns([
                Tables\Columns\TextColumn::make('candidate_name')->searchable(),
                Tables\Columns\TextColumn::make('email')->placeholder('—'),
                Tables\Columns\TextColumn::make('phone')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'Hired' => 'success',
                    'Offer' => 'info',
                    'Interview' => 'warning',
                    'Rejected' => 'danger',
                    default => 'gray',
                }),
                Tables\Columns\TextColumn::make('background_check_status')->label('Background Check')->badge()->placeholder('—'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('hire')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('This creates a new Staff record from this candidate\'s details.')
                    ->visible(fn (JobApplication $record) => ! in_array($record->status, ['Hired', 'Rejected']))
                    ->action(function (JobApplication $record) {
                        $staff = $record->hire();
                        Notification::make()->title('Candidate hired as ' . $staff->staff_number)->success()->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
