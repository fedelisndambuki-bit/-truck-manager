<?php

namespace App\Filament\Clusters\Finance\Resources;

use App\Filament\Clusters\Finance;
use App\Filament\Clusters\Finance\Resources\JournalEntryResource\Pages;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Services\PostingEngineService;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class JournalEntryResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'finance.journal_entries';
    }

    protected static ?string $model = JournalEntry::class;

    protected static ?string $cluster = Finance::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Journal Entries';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'journal_number';

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            'Posted' => 'success',
            'Reversed' => 'warning',
            'Cancelled' => 'danger',
            default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        $currency = session('currency', 'KES');

        return $form
            ->schema([
                Forms\Components\Section::make('Journal Details')->schema([
                    Forms\Components\DatePicker::make('date')->default(now())->required(),
                    Forms\Components\TextInput::make('reference'),
                    Forms\Components\Select::make('currency')
                        ->options(['KES' => 'KES', 'USD' => 'USD', 'EUR' => 'EUR', 'GBP' => 'GBP'])
                        ->default($currency)->required(),
                    Forms\Components\Textarea::make('description')->columnSpanFull(),
                ])->columns(3),
                Forms\Components\Section::make('Lines')
                    ->description('Debits must equal credits before this journal can be posted.')
                    ->schema([
                        Forms\Components\Repeater::make('lines')
                            ->relationship('lines')
                            ->schema([
                                Forms\Components\Select::make('account_id')
                                    ->label('Account')
                                    ->options(fn () => ChartOfAccount::withoutGlobalScope('company')
                                        ->where('status', 'Active')
                                        ->orderBy('account_code')
                                        ->get()
                                        ->mapWithKeys(fn (ChartOfAccount $a) => [$a->id => "{$a->account_code} — {$a->name}"]))
                                    ->searchable()->preload()->required(),
                                Forms\Components\Select::make('cost_centre_id')
                                    ->label('Cost Centre')
                                    ->relationship('costCentre', 'name')
                                    ->searchable()->preload(),
                                Forms\Components\Select::make('profit_centre_id')
                                    ->label('Profit Centre')
                                    ->relationship('profitCentre', 'name')
                                    ->searchable()->preload(),
                                Forms\Components\TextInput::make('debit')->numeric()->prefix($currency)->default(0)->required(),
                                Forms\Components\TextInput::make('credit')->numeric()->prefix($currency)->default(0)->required(),
                                Forms\Components\TextInput::make('description'),
                            ])
                            ->columns(3)
                            ->minItems(2)
                            ->defaultItems(2)
                            ->addActionLabel('Add Line')
                            ->reorderable(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $currency = session('currency', 'KES');

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('journal_number')->label('Journal #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('date')->date()->sortable(),
                Tables\Columns\TextColumn::make('source_module')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('description')->limit(50)->toggleable(),
                Tables\Columns\TextColumn::make('lines_sum_debit')->label('Total')->sum('lines', 'debit')->money($currency),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => static::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'Draft' => 'Draft', 'Posted' => 'Posted', 'Reversed' => 'Reversed', 'Cancelled' => 'Cancelled',
                ]),
                Tables\Filters\SelectFilter::make('source_module')->options(fn () => JournalEntry::query()
                    ->distinct()->pluck('source_module', 'source_module')->all()),
            ])
            ->actions([
                Tables\Actions\Action::make('post')
                    ->label('Post')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (JournalEntry $record) => $record->canPost())
                    ->requiresConfirmation()
                    ->action(function (JournalEntry $record) {
                        try {
                            app(PostingEngineService::class)->postJournal($record);
                            Notification::make()->title('Journal posted')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Could not post journal')->body($e->getMessage())->danger()->send();
                        }
                    }),
                Tables\Actions\Action::make('reverse')
                    ->label('Reverse')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (JournalEntry $record) => $record->canReverse())
                    ->form([
                        Forms\Components\Textarea::make('reason')->required(),
                    ])
                    ->action(function (JournalEntry $record, array $data) {
                        try {
                            app(PostingEngineService::class)->reverseJournal($record, $data['reason']);
                            Notification::make()->title('Journal reversed')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Could not reverse journal')->body($e->getMessage())->danger()->send();
                        }
                    }),
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (JournalEntry $record) => $record->canCancel())
                    ->requiresConfirmation()
                    ->action(function (JournalEntry $record) {
                        app(PostingEngineService::class)->cancel($record);
                        Notification::make()->title('Journal cancelled')->success()->send();
                    }),
                Tables\Actions\EditAction::make()->visible(fn (JournalEntry $record) => $record->status === 'Draft'),
            ])
            ->defaultSort('id', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJournalEntries::route('/'),
            'create' => Pages\CreateJournalEntry::route('/create'),
            'edit' => Pages\EditJournalEntry::route('/{record}/edit'),
        ];
    }
}
