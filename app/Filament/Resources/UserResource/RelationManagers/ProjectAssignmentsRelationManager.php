<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ProjectAssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'projectUsers';

    protected static ?string $title = 'Project Assignments';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('project_id')
                ->label('Project')
                ->options(fn () => Project::query()->pluck('name', 'id'))
                ->searchable()
                ->required(),
            Forms\Components\TextInput::make('role')
                ->label('Portal Role')
                ->helperText('Free-text label for display — actual module access is governed by the user\'s real role.'),
            Forms\Components\Select::make('status')
                ->options(['Active' => 'Active', 'Suspended' => 'Suspended', 'Expired' => 'Expired'])
                ->default('Active')
                ->required(),
            Forms\Components\DatePicker::make('start_date')->default(now()),
            Forms\Components\DatePicker::make('end_date')->label('Expiry Date')->helperText('Leave blank for no expiry.'),
            Forms\Components\Fieldset::make('Capabilities')
                ->schema([
                    Forms\Components\Toggle::make('can_view_drawings')->label('Can View Drawings')->default(true),
                    Forms\Components\Toggle::make('can_download_documents')->label('Can Download Documents')->default(true),
                    Forms\Components\Toggle::make('can_upload_documents')->label('Can Upload Documents')->default(false),
                    Forms\Components\Toggle::make('can_comment')->label('Can Comment')->default(true),
                    Forms\Components\Toggle::make('can_approve')->label('Can Approve')->default(false),
                    Forms\Components\Toggle::make('can_view_financials')->label('Can View Financials')->default(false),
                ])->columns(3),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('role')
            ->columns([
                Tables\Columns\TextColumn::make('project.name')->label('Project')->searchable(),
                Tables\Columns\TextColumn::make('role')->label('Portal Role')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'Active' => 'success', 'Suspended' => 'danger', default => 'gray',
                }),
                Tables\Columns\TextColumn::make('start_date')->date(),
                Tables\Columns\TextColumn::make('end_date')->label('Expires')->date()->placeholder('Never'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        $data['company_id'] = Auth::user()?->company_id;
                        $data['invited_by_id'] = Auth::id();
                        $data['invited_at'] = now();

                        return $data;
                    }),
            ])
            ->actions([
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
