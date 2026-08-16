<?php

namespace App\Filament\Clusters\ProjectFinance\Resources;

use App\Filament\Clusters\ProjectFinance;
use App\Filament\Clusters\ProjectFinance\Resources\ClientInvoiceResource\Pages;
use App\Models\Invoice;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClientInvoiceResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'project_finance.view';
    }

    protected static ?string $model = Invoice::class;

    protected static ?string $cluster = ProjectFinance::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationLabel = 'Client Invoices';

    protected static ?int $navigationSort = 11;

    protected static ?string $recordTitleAttribute = 'invoice_number';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNotNull('project_id')->with(['project', 'client']);
    }

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            'paid' => 'success',
            'sent' => 'info',
            'overdue' => 'danger',
            default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        $currency = session('currency', 'KES');

        return $form
            ->schema([
                Forms\Components\Hidden::make('line_items')->default([]),
                Forms\Components\Section::make('Invoice Details')->schema([
                    Forms\Components\TextInput::make('invoice_number')
                        ->default(fn () => 'CINV-' . str_pad((string) (Invoice::withoutGlobalScope('company')->max('id') + 1), 4, '0', STR_PAD_LEFT))
                        ->disabled()->dehydrated()->required(),
                    Forms\Components\Select::make('type')->options([
                        'invoice' => 'Invoice', 'credit_note' => 'Credit Note',
                    ])->default('invoice')->required(),
                    Forms\Components\Select::make('project_id')
                        ->relationship('project', 'name')
                        ->searchable()->preload()->required()->live()
                        ->afterStateUpdated(function (Forms\Set $set, $state) {
                            $set('client_id', \App\Models\Project::find($state)?->client_id);
                        }),
                    Forms\Components\Select::make('client_id')
                        ->relationship('client', 'company_name')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('progress_claim_id')
                        ->label('Progress Claim')
                        ->relationship('progressClaim', 'claim_number')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('status')->options([
                        'draft' => 'Draft', 'sent' => 'Sent', 'paid' => 'Paid', 'overdue' => 'Overdue',
                    ])->default('draft')->required(),
                    Forms\Components\DatePicker::make('invoice_date')->default(now())->required(),
                    Forms\Components\DatePicker::make('due_date')->required(),
                ])->columns(2),
                Forms\Components\Section::make('Amounts')->schema([
                    Forms\Components\TextInput::make('subtotal')->numeric()->prefix($currency)->required()->live(),
                    Forms\Components\TextInput::make('tax')->numeric()->prefix($currency)->default(0)->live(),
                    Forms\Components\TextInput::make('total_amount')->numeric()->prefix($currency)
                        ->required()
                        ->helperText('Subtotal + Tax'),
                ])->columns(3),
                Forms\Components\Section::make('Attachments')->schema([
                    Forms\Components\FileUpload::make('attachment_path')
                        ->label('Supporting Document')
                        ->directory('project-finance/invoices')
                        ->columnSpanFull(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $currency = session('currency', 'KES');

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')->label('Invoice #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->badge()->color(fn (string $state) => $state === 'credit_note' ? 'warning' : 'gray'),
                Tables\Columns\TextColumn::make('project.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('client.company_name')->label('Client')->searchable(),
                Tables\Columns\TextColumn::make('total_amount')->money($currency)->sortable(),
                Tables\Columns\TextColumn::make('due_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => static::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')->label('Project')->relationship('project', 'name'),
                Tables\Filters\SelectFilter::make('status')->options([
                    'draft' => 'Draft', 'sent' => 'Sent', 'paid' => 'Paid', 'overdue' => 'Overdue',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('markPaid')
                    ->label('Mark Paid')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (Invoice $record) => $record->status !== 'paid')
                    ->requiresConfirmation()
                    ->action(fn (Invoice $record) => $record->update(['status' => 'paid'])),
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
            'index' => Pages\ListClientInvoices::route('/'),
            'create' => Pages\CreateClientInvoice::route('/create'),
            'edit' => Pages\EditClientInvoice::route('/{record}/edit'),
        ];
    }
}
