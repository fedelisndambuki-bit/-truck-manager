<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentCertificateResource\Pages;
use App\Models\PaymentCertificate;
use App\Traits\HasModuleAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentCertificateResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'subcontractors.view';
    }

    protected static ?string $model = PaymentCertificate::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Payment Certificates';

    protected static ?string $navigationGroup = 'Subcontractor Management';

    protected static ?int $navigationSort = 12;

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            'Paid' => 'success',
            'Approved' => 'info',
            'Submitted' => 'warning',
            default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Certificate Details')->schema([
                    Forms\Components\Select::make('contract_id')
                        ->label('Contract')
                        ->relationship('contract', 'contract_number')
                        ->searchable()->preload()->required()->live(),
                    Forms\Components\Select::make('certificate_type')
                        ->options(['Interim' => 'Interim', 'Final' => 'Final'])
                        ->default('Interim')->required(),
                    Forms\Components\TextInput::make('period_covered')->placeholder('e.g. Jan 2026'),
                    Forms\Components\Select::make('status')
                        ->options([
                            'Draft' => 'Draft', 'Submitted' => 'Submitted',
                            'Approved' => 'Approved', 'Paid' => 'Paid',
                        ])->default('Draft')->required(),
                ])->columns(2),
                Forms\Components\Section::make('Valuation')->schema([
                    Forms\Components\TextInput::make('gross_amount')->numeric()->prefix(session('currency', 'KES'))->required()->live(onBlur: true),
                    Forms\Components\TextInput::make('advance_recovery_amount')->numeric()->prefix(session('currency', 'KES'))->default(0),
                    Forms\Components\TextInput::make('previous_certified_amount')->numeric()->prefix(session('currency', 'KES'))->default(0),
                    Forms\Components\Placeholder::make('retention_amount_display')
                        ->label('Retention (auto-calculated)')
                        ->content(function (Forms\Get $get) {
                            $contract = \App\Models\Contract::find($get('contract_id'));
                            $percent = (float) ($contract?->retention_percent ?? 0);
                            $amount = (float) ($get('gross_amount') ?? 0) * $percent / 100;

                            return session('currency', 'KES') . ' ' . number_format($amount, 2) . " ({$percent}%)";
                        }),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('certificate_number')->label('Certificate #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('contract.contract_number')->label('Contract')->searchable(),
                Tables\Columns\TextColumn::make('contract.subcontractor.company_name')->label('Subcontractor')->searchable(),
                Tables\Columns\TextColumn::make('certificate_type')->label('Type'),
                Tables\Columns\TextColumn::make('gross_amount')->money(session('currency', 'KES'))->sortable(),
                Tables\Columns\TextColumn::make('net_amount_due')->label('Net Due')->money(session('currency', 'KES'))->sortable(),
                Tables\Columns\TextColumn::make('outstanding_balance')->label('Outstanding')->money(session('currency', 'KES'))
                    ->state(fn (PaymentCertificate $record) => $record->outstanding_balance),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => static::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'Draft' => 'Draft', 'Submitted' => 'Submitted',
                    'Approved' => 'Approved', 'Paid' => 'Paid',
                ]),
                Tables\Filters\SelectFilter::make('certificate_type')->label('Type')->options(['Interim' => 'Interim', 'Final' => 'Final']),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (PaymentCertificate $record) => $record->status === 'Submitted')
                    ->requiresConfirmation()
                    ->action(fn (PaymentCertificate $record) => $record->approve()),
                Tables\Actions\Action::make('markPaid')
                    ->label('Mark Paid')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('info')
                    ->visible(fn (PaymentCertificate $record) => $record->status === 'Approved')
                    ->requiresConfirmation()
                    ->action(fn (PaymentCertificate $record) => $record->markPaid()),
                Tables\Actions\Action::make('exportPdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->action(function (PaymentCertificate $record) {
                        $record->load(['contract.subcontractor']);
                        $pdf = Pdf::loadView('reports.payment-certificate', [
                            'title' => 'Payment Certificate — ' . $record->certificate_number,
                            'date' => now()->format('d M Y'),
                            'certificate' => $record,
                            'currency' => session('currency', 'KES'),
                        ]);

                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->stream();
                        }, 'payment-certificate-' . $record->certificate_number . '.pdf');
                    }),
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
            'index' => Pages\ListPaymentCertificates::route('/'),
            'create' => Pages\CreatePaymentCertificate::route('/create'),
            'edit' => Pages\EditPaymentCertificate::route('/{record}/edit'),
        ];
    }
}
