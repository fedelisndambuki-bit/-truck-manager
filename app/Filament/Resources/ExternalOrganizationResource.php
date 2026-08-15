<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExternalOrganizationResource\Pages;
use App\Models\ExternalOrganization;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExternalOrganizationResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'user-management';
    }

    protected static ?string $model = ExternalOrganization::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'External Organizations';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('External Organization')->schema([
                Forms\Components\TextInput::make('name')->required()->unique(ignoreRecord: true),
                Forms\Components\Select::make('type')->options([
                    'Consultant' => 'Consultant', 'Architect' => 'Architect',
                    'Structural Engineer' => 'Structural Engineer', 'MEP Engineer' => 'MEP Engineer',
                    'Quantity Surveyor' => 'Quantity Surveyor', 'Clerk of Works' => 'Clerk of Works',
                    'Supplier' => 'Supplier', 'Subcontractor' => 'Subcontractor',
                    'Auditor' => 'Auditor', 'Other' => 'Other',
                ])->default('Consultant')->required(),
                Forms\Components\TextInput::make('contact_person'),
                Forms\Components\TextInput::make('phone')->tel(),
                Forms\Components\TextInput::make('email')->email(),
                Forms\Components\Select::make('status')->options([
                    'Active' => 'Active', 'Inactive' => 'Inactive',
                ])->default('Active')->required(),
                Forms\Components\Textarea::make('address')->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->badge()->color('info'),
                Tables\Columns\TextColumn::make('contact_person')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('phone')->toggleable(),
                Tables\Columns\TextColumn::make('email')->toggleable(),
                Tables\Columns\TextColumn::make('users_count')->label('Users')->counts('users'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => $state === 'Active' ? 'success' : 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->options([
                    'Consultant' => 'Consultant', 'Architect' => 'Architect',
                    'Structural Engineer' => 'Structural Engineer', 'MEP Engineer' => 'MEP Engineer',
                    'Quantity Surveyor' => 'Quantity Surveyor', 'Clerk of Works' => 'Clerk of Works',
                    'Supplier' => 'Supplier', 'Subcontractor' => 'Subcontractor',
                    'Auditor' => 'Auditor', 'Other' => 'Other',
                ]),
                Tables\Filters\SelectFilter::make('status')->options(['Active' => 'Active', 'Inactive' => 'Inactive']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListExternalOrganizations::route('/'),
            'create' => Pages\CreateExternalOrganization::route('/create'),
            'edit' => Pages\EditExternalOrganization::route('/{record}/edit'),
        ];
    }
}
