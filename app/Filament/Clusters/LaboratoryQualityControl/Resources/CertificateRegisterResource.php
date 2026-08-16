<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources;

use App\Filament\Clusters\LaboratoryQualityControl;
use App\Filament\Clusters\LaboratoryQualityControl\Resources\CertificateRegisterResource\Pages;
use App\Models\Certificate;
use App\Services\Quarry\LabCertificatePdfService;
use App\Traits\HasModuleAccess;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Certificates" — 2nd Resource over the same Certificate table
 * CertificateResource ("Product Certification") manages, unscoped
 * read/search/reprint-focused master register of every certificate ever
 * generated. Mirrors the QualityHold/QualityRelease split and DispatchOrder's
 * many-Resources-over-one-table precedent.
 */
class CertificateRegisterResource extends Resource
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

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Certificates';

    protected static ?int $navigationSort = 15;

    protected static ?string $slug = 'certificate-register';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['customer', 'material']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('certificate_number')->label('Certificate #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('customer.company_name')->label('Customer')->placeholder('—')->searchable(),
                Tables\Columns\TextColumn::make('material.name')->label('Product')->placeholder('—'),
                Tables\Columns\TextColumn::make('batch_number')->placeholder('—')->searchable(),
                Tables\Columns\TextColumn::make('issued_at')->dateTime('d M Y H:i')->sortable()->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => CertificateResource::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->options(array_combine(Certificate::TYPES, Certificate::TYPES)),
                Tables\Filters\SelectFilter::make('status')->options(array_combine(Certificate::STATUSES, Certificate::STATUSES)),
                Tables\Filters\SelectFilter::make('customer_id')->label('Customer')->relationship('customer', 'company_name'),
            ])
            ->actions([
                Tables\Actions\Action::make('downloadPdf')
                    ->label('Reprint PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->action(fn (Certificate $record) => app(LabCertificatePdfService::class)->generate($record)),
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListCertificateRegister::route('/'),
        ];
    }
}
