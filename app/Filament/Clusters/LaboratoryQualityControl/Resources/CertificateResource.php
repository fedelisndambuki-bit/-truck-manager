<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources;

use App\Filament\Clusters\LaboratoryQualityControl;
use App\Filament\Clusters\LaboratoryQualityControl\Resources\CertificateResource\Pages;
use App\Models\Certificate;
use App\Services\Quarry\LabCertificatePdfService;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Product Certification" — the primary, generation-focused Resource over
 * Certificate: create from an approved LabSample, Issue/Revoke actions,
 * PDF+QR download via LabCertificatePdfService. "Certificates" (near
 * Standards) is a 2nd, read/register-focused Resource over this same table.
 */
class CertificateResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = LaboratoryQualityControl::class;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'lab.view';
    }

    protected static ?string $model = Certificate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationLabel = 'Product Certification';

    protected static ?int $navigationSort = 5;

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Issued' => 'success',
            'Revoked' => 'danger',
            default => 'gray',
        };
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['sample', 'customer', 'material']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Certificate')->schema([
                    Forms\Components\TextInput::make('certificate_number')->label('Certificate #')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\Select::make('type')->options(array_combine(Certificate::TYPES, Certificate::TYPES))->required(),
                    Forms\Components\Select::make('lab_sample_id')->label('Sample')
                        ->relationship('sample', 'sample_number')->searchable()->preload(),
                    Forms\Components\Select::make('customer_id')->label('Customer')
                        ->relationship('customer', 'company_name')->searchable()->preload(),
                    Forms\Components\Select::make('material_id')->label('Product')
                        ->relationship('material', 'name')->searchable()->preload(),
                    Forms\Components\TextInput::make('batch_number')->label('Batch Number'),
                    Forms\Components\Select::make('dispatch_order_id')->label('Dispatch Order')
                        ->relationship('dispatchOrder', 'dispatch_number')->searchable()->preload(),
                    Forms\Components\Select::make('status')->options(array_combine(Certificate::STATUSES, Certificate::STATUSES))->default('Draft')->required(),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('certificate_number')->label('Certificate #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('sample.sample_number')->label('Sample')->placeholder('—'),
                Tables\Columns\TextColumn::make('customer.company_name')->label('Customer')->placeholder('—'),
                Tables\Columns\TextColumn::make('material.name')->label('Product')->placeholder('—'),
                Tables\Columns\TextColumn::make('batch_number')->placeholder('—'),
                Tables\Columns\TextColumn::make('issued_at')->dateTime('d M Y H:i')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(array_combine(Certificate::STATUSES, Certificate::STATUSES)),
                Tables\Filters\SelectFilter::make('type')->options(array_combine(Certificate::TYPES, Certificate::TYPES)),
            ])
            ->actions([
                Tables\Actions\Action::make('issue')
                    ->label('Issue')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Certificate $record) => $record->status === 'Draft')
                    ->form([
                        Forms\Components\Select::make('issued_by_id')->label('Issued By')
                            ->relationship('issuedBy', 'full_name')->searchable()->preload()->required(),
                    ])
                    ->action(function (Certificate $record, array $data) {
                        $record->issue($data['issued_by_id']);
                        Notification::make()->title('Certificate issued')->success()->send();
                    }),
                Tables\Actions\Action::make('revoke')
                    ->label('Revoke')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Certificate $record) => $record->status === 'Issued')
                    ->action(function (Certificate $record) {
                        $record->revoke();
                        Notification::make()->title('Certificate revoked')->warning()->send();
                    }),
                Tables\Actions\Action::make('downloadPdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->action(fn (Certificate $record) => app(LabCertificatePdfService::class)->generate($record)),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->striped();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['certificate_number', 'batch_number'];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCertificates::route('/'),
            'create' => Pages\CreateCertificate::route('/create'),
            'edit' => Pages\EditCertificate::route('/{record}/edit'),
        ];
    }
}
