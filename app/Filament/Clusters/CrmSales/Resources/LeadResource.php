<?php

namespace App\Filament\Clusters\CrmSales\Resources;

use App\Filament\Clusters\CrmSales;
use App\Filament\Clusters\CrmSales\Resources\LeadResource\Pages;
use App\Models\Lead;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LeadResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'crm.view';
    }

    protected static ?string $model = Lead::class;

    protected static ?string $cluster = CrmSales::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Leads';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'company_name';

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            'Converted' => 'success',
            'Lost' => 'danger',
            'Negotiation', 'Proposal Sent' => 'warning',
            'Qualified', 'Contacted' => 'info',
            default => 'gray',
        };
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['assignedTo']);
    }

    public static function form(Form $form): Form
    {
        $currency = session('currency', 'KES');

        return $form
            ->schema([
                Forms\Components\Section::make('Lead Details')->schema([
                    Forms\Components\TextInput::make('source'),
                    Forms\Components\TextInput::make('company_name')->required(),
                    Forms\Components\TextInput::make('contact_person'),
                    Forms\Components\TextInput::make('industry'),
                    Forms\Components\TextInput::make('country'),
                    Forms\Components\TextInput::make('region'),
                    Forms\Components\Textarea::make('address')->columnSpanFull(),
                    Forms\Components\TextInput::make('phone'),
                    Forms\Components\TextInput::make('email')->email(),
                    Forms\Components\TextInput::make('website')->url(),
                ])->columns(3),
                Forms\Components\Section::make('Opportunity Sizing')->schema([
                    Forms\Components\Textarea::make('interest')->label('Interest / Products')->columnSpanFull(),
                    Forms\Components\TextInput::make('expected_volume')->numeric(),
                    Forms\Components\TextInput::make('estimated_value')->numeric()->prefix($currency),
                    Forms\Components\Select::make('assigned_to_id')
                        ->label('Assigned Salesperson')
                        ->relationship('assignedTo', 'full_name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('priority')
                        ->options(['Low' => 'Low', 'Normal' => 'Normal', 'High' => 'High', 'Urgent' => 'Urgent'])
                        ->default('Normal')->required(),
                    Forms\Components\Select::make('status')
                        ->options(array_combine(
                            $statuses = ['New', 'Qualified', 'Contacted', 'Proposal Sent', 'Negotiation', 'Converted', 'Lost'],
                            $statuses
                        ))
                        ->default('New')->required()
                        ->disabled(fn (?Lead $record) => $record?->status === 'Converted'),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        $currency = session('currency', 'KES');

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('lead_number')->label('Lead #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('company_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('source')->toggleable(),
                Tables\Columns\TextColumn::make('priority')->badge(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => static::getStatusColor($state)),
                Tables\Columns\TextColumn::make('assignedTo.full_name')->label('Assigned To')->placeholder('Unassigned'),
                Tables\Columns\TextColumn::make('estimated_value')->money($currency)->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(array_combine(
                    $statuses = ['New', 'Qualified', 'Contacted', 'Proposal Sent', 'Negotiation', 'Converted', 'Lost'],
                    $statuses
                )),
                Tables\Filters\SelectFilter::make('priority')->options(['Low' => 'Low', 'Normal' => 'Normal', 'High' => 'High', 'Urgent' => 'Urgent']),
            ])
            ->actions([
                Tables\Actions\Action::make('convert')
                    ->label('Convert to Client')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Lead $record) => ! in_array($record->status, ['Converted', 'Lost'], true))
                    ->action(function (Lead $record) {
                        $client = $record->convertToClient();
                        Notification::make()->title("Converted to client {$client->company_name}")->success()->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'edit' => Pages\EditLead::route('/{record}/edit'),
        ];
    }
}
