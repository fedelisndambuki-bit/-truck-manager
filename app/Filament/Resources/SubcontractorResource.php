<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubcontractorResource\Pages;
use App\Models\Subcontractor;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubcontractorResource extends Resource
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

    protected static ?string $model = Subcontractor::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = 'Subcontractors';

    protected static ?string $navigationGroup = 'Subcontractor Management';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'company_name';

    public static function getCategoryOptions(): array
    {
        return [
            'General Trade' => 'General Trade',
            'Specialist' => 'Specialist',
            'Nominated' => 'Nominated',
            'Domestic' => 'Domestic',
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            'Prequalified' => 'Prequalified',
            'Active' => 'Active',
            'Suspended' => 'Suspended',
            'Blacklisted' => 'Blacklisted',
            'Inactive' => 'Inactive',
        ];
    }

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            'Active' => 'success',
            'Prequalified' => 'info',
            'Suspended' => 'warning',
            'Blacklisted', 'Terminated' => 'danger',
            default => 'gray',
        };
    }

    public static function companyProfileSchema(): array
    {
        return [
            Forms\Components\TextInput::make('company_name')->required(),
            Forms\Components\TextInput::make('registration_number'),
            Forms\Components\TextInput::make('tax_pin')->label('Tax PIN'),
            Forms\Components\TextInput::make('business_license'),
            Forms\Components\Select::make('category')->options(self::getCategoryOptions()),
            Forms\Components\Select::make('specialization_id')
                ->label('Specialization')
                ->relationship('specialization', 'name')
                ->searchable()->preload()
                ->createOptionForm([Forms\Components\TextInput::make('name')->required()]),
            Forms\Components\TextInput::make('years_in_business')->numeric(),
            Forms\Components\TextInput::make('number_of_employees')->numeric(),
            Forms\Components\Textarea::make('equipment_owned')->columnSpanFull(),
        ];
    }

    public static function contactLocationSchema(): array
    {
        return [
            Forms\Components\TextInput::make('contact_person')->label('Primary Contact'),
            Forms\Components\TextInput::make('email')->email(),
            Forms\Components\TextInput::make('phone')->tel(),
            Forms\Components\TextInput::make('website')->url(),
            Forms\Components\Textarea::make('office_address')->columnSpanFull(),
            Forms\Components\TextInput::make('country'),
            Forms\Components\TextInput::make('county'),
            Forms\Components\TextInput::make('city'),
        ];
    }

    public static function bankInsuranceSchema(): array
    {
        return [
            Forms\Components\TextInput::make('bank_name'),
            Forms\Components\TextInput::make('bank_account_number'),
            Forms\Components\TextInput::make('bank_branch'),
            Forms\Components\TextInput::make('insurance_provider'),
            Forms\Components\TextInput::make('insurance_policy_number'),
            Forms\Components\DatePicker::make('insurance_expiry_date'),
        ];
    }

    public static function complianceDatesSchema(): array
    {
        return [
            Forms\Components\DatePicker::make('business_license_expiry_date'),
            Forms\Components\TextInput::make('nca_registration_number')->label('NCA Registration Number'),
            Forms\Components\DatePicker::make('nca_expiry_date')->label('NCA Expiry Date'),
            Forms\Components\Select::make('tax_compliance_status')->options([
                'Compliant' => 'Compliant', 'Non-Compliant' => 'Non-Compliant', 'Unknown' => 'Unknown',
            ])->default('Unknown'),
            Forms\Components\Select::make('safety_compliance_status')->options([
                'Compliant' => 'Compliant', 'Non-Compliant' => 'Non-Compliant', 'Unknown' => 'Unknown',
            ])->default('Unknown'),
            Forms\Components\Select::make('quality_compliance_status')->options([
                'Compliant' => 'Compliant', 'Non-Compliant' => 'Non-Compliant', 'Unknown' => 'Unknown',
            ])->default('Unknown'),
        ];
    }

    public static function statusSchema(): array
    {
        return [
            Forms\Components\Select::make('status')->options(self::getStatusOptions())->default('Prequalified')->required(),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Subcontractor')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Company Profile')->schema(self::companyProfileSchema())->columns(2),
                        Forms\Components\Tabs\Tab::make('Contact & Location')->schema(self::contactLocationSchema())->columns(2),
                        Forms\Components\Tabs\Tab::make('Bank & Insurance')->schema(self::bankInsuranceSchema())->columns(2),
                        Forms\Components\Tabs\Tab::make('Compliance Dates')->schema(self::complianceDatesSchema())->columns(2),
                        Forms\Components\Tabs\Tab::make('Status')->schema(self::statusSchema()),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('subcontractor_code')->label('Code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('company_name')->searchable()->sortable()
                    ->url(fn (Subcontractor $record) => self::getUrl('view', ['record' => $record])),
                Tables\Columns\TextColumn::make('specialization.name')->label('Specialization')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('category')->badge()->color('gray')->placeholder('—'),
                Tables\Columns\TextColumn::make('contact_person')->label('Contact')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('compliance_status')->label('Compliance')
                    ->badge()
                    ->color(fn (Subcontractor $record) => $record->compliance_status === 'Compliant' ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('average_performance_rating')->label('Avg. Rating')->suffix(' / 5')->placeholder('—')
                    ->state(fn (Subcontractor $record) => $record->average_performance_rating ? round($record->average_performance_rating, 1) : null),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(self::getStatusOptions()),
                Tables\Filters\SelectFilter::make('specialization_id')->label('Specialization')->relationship('specialization', 'name'),
                Tables\Filters\SelectFilter::make('category')->options(self::getCategoryOptions()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalIcon('heroicon-o-eye')
                    ->modalIconColor('gray'),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('company_name')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubcontractors::route('/'),
            'create' => Pages\CreateSubcontractor::route('/create'),
            'edit' => Pages\EditSubcontractor::route('/{record}/edit'),
            'view' => Pages\ViewSubcontractor::route('/{record}'),
        ];
    }
}
