<?php

namespace App\Filament\Clusters\CrmSales\Resources;

use App\Filament\Clusters\CrmSales;
use App\Filament\Clusters\CrmSales\Resources\ContactResource\Pages;
use App\Models\Contact;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContactResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'crm.view';
    }

    protected static ?string $model = Contact::class;

    protected static ?string $cluster = CrmSales::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationLabel = 'Contacts';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['client']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Contact Details')->schema([
                    Forms\Components\Select::make('client_id')
                        ->label('Client')
                        ->relationship('client', 'company_name')
                        ->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('full_name')->required(),
                    Forms\Components\TextInput::make('role'),
                    Forms\Components\TextInput::make('department'),
                    Forms\Components\TextInput::make('phone'),
                    Forms\Components\TextInput::make('mobile'),
                    Forms\Components\TextInput::make('email')->email(),
                    Forms\Components\TextInput::make('preferred_communication'),
                    Forms\Components\Select::make('status')->options(['Active' => 'Active', 'Inactive' => 'Inactive'])->default('Active')->required(),
                ])->columns(3),
                Forms\Components\Section::make('Roles')->schema([
                    Forms\Components\Toggle::make('is_decision_maker')->label('Decision Maker'),
                    Forms\Components\Toggle::make('is_technical_contact')->label('Technical Contact'),
                    Forms\Components\Toggle::make('is_finance_contact')->label('Finance Contact'),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('client.company_name')->label('Client')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('role')->toggleable(),
                Tables\Columns\TextColumn::make('phone')->toggleable(),
                Tables\Columns\TextColumn::make('email')->toggleable(),
                Tables\Columns\IconColumn::make('is_decision_maker')->label('Decision Maker')->boolean()->toggleable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => $state === 'Active' ? 'success' : 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('client_id')->relationship('client', 'company_name'),
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
            'index' => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            'edit' => Pages\EditContact::route('/{record}/edit'),
        ];
    }
}
