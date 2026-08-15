<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources;

use App\Filament\Clusters\LaboratoryQualityControl;
use App\Filament\Clusters\LaboratoryQualityControl\Resources\CustomerQualityRequirementResource\Pages;
use App\Models\CustomerQualityRequirement;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * "Customer Quality Requirements" — not in the spec's literal sidebar list,
 * added here (nested near Standards) since the spec's own "CUSTOMER QUALITY
 * REQUIREMENTS" section explicitly requires automatic pre-dispatch
 * validation against customer-specific specs, which needs a real place to
 * store/manage them. Validated via CustomerQualityRequirement::
 * validateMeasuredValues() against a sample's actual LabTest results.
 */
class CustomerQualityRequirementResource extends Resource
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

    protected static ?string $model = CustomerQualityRequirement::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationLabel = 'Customer Quality Requirements';

    protected static ?int $navigationSort = 14;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Customer Specification')->schema([
                    Forms\Components\Select::make('customer_id')->label('Customer')
                        ->relationship('customer', 'company_name')->searchable()->preload()->required(),
                    Forms\Components\Select::make('material_id')->label('Product')
                        ->relationship('material', 'name')->searchable()->preload()->required(),
                    Forms\Components\Select::make('status')->options(array_combine(CustomerQualityRequirement::STATUSES, CustomerQualityRequirement::STATUSES))->default('Active')->required(),
                ])->columns(3),

                Forms\Components\Section::make('Limits')->schema([
                    Forms\Components\TextInput::make('max_dust_percent')->label('Maximum Dust %')->numeric()->suffix('%'),
                    Forms\Components\TextInput::make('min_strength')->label('Minimum Strength'),
                    Forms\Components\TextInput::make('moisture_limit')->label('Moisture Limit %')->numeric()->suffix('%'),
                    Forms\Components\KeyValue::make('particle_size_distribution')->label('Particle Size Distribution')->columnSpanFull(),
                    Forms\Components\Textarea::make('custom_testing')->label('Custom Testing Requirements')->columnSpanFull(),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.company_name')->label('Customer')->searchable(),
                Tables\Columns\TextColumn::make('material.name')->label('Product')->searchable(),
                Tables\Columns\TextColumn::make('max_dust_percent')->label('Max Dust')->suffix('%')->placeholder('—'),
                Tables\Columns\TextColumn::make('min_strength')->label('Min Strength')->placeholder('—'),
                Tables\Columns\TextColumn::make('moisture_limit')->label('Moisture Limit')->suffix('%')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => $state === 'Active' ? 'success' : 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(array_combine(CustomerQualityRequirement::STATUSES, CustomerQualityRequirement::STATUSES)),
            ])
            ->actions([
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomerQualityRequirements::route('/'),
            'create' => Pages\CreateCustomerQualityRequirement::route('/create'),
            'view' => Pages\ViewCustomerQualityRequirement::route('/{record}'),
            'edit' => Pages\EditCustomerQualityRequirement::route('/{record}/edit'),
        ];
    }
}
