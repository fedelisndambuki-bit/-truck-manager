<?php

namespace App\Filament\Clusters\ProjectFinance\Resources;

use App\Filament\Clusters\ProjectFinance;
use App\Filament\Clusters\ProjectFinance\Resources\CertificateResource\Pages;
use App\Models\PaymentCertificate;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CertificateResource extends Resource
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

    protected static ?string $model = PaymentCertificate::class;

    protected static ?string $cluster = ProjectFinance::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationLabel = 'Payment Certificates';

    protected static ?int $navigationSort = 12;

    protected static ?string $recordTitleAttribute = 'certificate_number';

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            'Approved', 'Paid' => 'success',
            'Draft' => 'gray',
            'Rejected' => 'danger',
            default => 'warning',
        };
    }

    public static function form(Form $form): Form
    {
        $currency = session('currency', 'KES');

        return $form
            ->schema([
                Forms\Components\Section::make('Certificate Details')->schema([
                    Forms\Components\Select::make('scope')->options([
                        'client' => 'Client', 'subcontractor' => 'Subcontractor',
                    ])->default('client')->required()->live(),
                    Forms\Components\Select::make('certificate_type')->options([
                        'Interim' => 'Interim', 'Final' => 'Final', 'Retention' => 'Retention',
                    ])->default('Interim')->required(),
                    Forms\Components\Select::make('project_id')
                        ->relationship('project', 'name')
                        ->searchable()->preload()
                        ->visible(fn (Forms\Get $get) => $get('scope') === 'client')
                        ->required(fn (Forms\Get $get) => $get('scope') === 'client'),
                    Forms\Components\Select::make('progress_claim_id')
                        ->label('Progress Claim')
                        ->relationship('progressClaim', 'claim_number')
                        ->searchable()->preload()
                        ->visible(fn (Forms\Get $get) => $get('scope') === 'client'),
                    Forms\Components\Select::make('contract_id')
                        ->label('Subcontract')
                        ->relationship('contract', 'contract_number')
                        ->searchable()->preload()
                        ->visible(fn (Forms\Get $get) => $get('scope') === 'subcontractor')
                        ->required(fn (Forms\Get $get) => $get('scope') === 'subcontractor'),
                    Forms\Components\TextInput::make('period_covered'),
                    Forms\Components\Select::make('status')->options([
                        'Draft' => 'Draft', 'Submitted' => 'Submitted', 'Approved' => 'Approved',
                        'Paid' => 'Paid', 'Rejected' => 'Rejected',
                    ])->default('Draft')->required(),
                ])->columns(2),
                Forms\Components\Section::make('Amounts')->schema([
                    Forms\Components\TextInput::make('gross_amount')->numeric()->prefix($currency)->required(),
                    Forms\Components\TextInput::make('advance_recovery_amount')->numeric()->prefix($currency)->default(0),
                    Forms\Components\TextInput::make('previous_certified_amount')->numeric()->prefix($currency)->default(0),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        $currency = session('currency', 'KES');

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('certificate_number')->label('Certificate #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('scope')->badge()->color(fn (string $state) => $state === 'client' ? 'info' : 'gray'),
                Tables\Columns\TextColumn::make('certificate_type')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('project.name')
                    ->getStateUsing(fn (PaymentCertificate $record) => $record->project?->name ?? $record->contract?->project?->name)
                    ->label('Project')->searchable(),
                Tables\Columns\TextColumn::make('gross_amount')->money($currency)->sortable(),
                Tables\Columns\TextColumn::make('retention_amount')->money($currency)->toggleable(),
                Tables\Columns\TextColumn::make('net_amount_due')->money($currency)->sortable(),
                Tables\Columns\TextColumn::make('paid_amount')->money($currency)->toggleable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => static::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('scope')->options(['client' => 'Client', 'subcontractor' => 'Subcontractor']),
                Tables\Filters\SelectFilter::make('status')->options([
                    'Draft' => 'Draft', 'Submitted' => 'Submitted', 'Approved' => 'Approved',
                    'Paid' => 'Paid', 'Rejected' => 'Rejected',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (PaymentCertificate $record) => $record->status === 'Submitted' && Auth::user()?->can('project_finance.approve'))
                    ->requiresConfirmation()
                    ->action(fn (PaymentCertificate $record) => $record->approve()),
                Tables\Actions\Action::make('markPaid')
                    ->label('Mark Paid')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (PaymentCertificate $record) => $record->status === 'Approved')
                    ->requiresConfirmation()
                    ->action(fn (PaymentCertificate $record) => $record->markPaid()),
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
