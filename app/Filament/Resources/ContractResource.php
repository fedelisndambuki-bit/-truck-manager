<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContractResource\Pages;
use App\Models\Contract;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContractResource extends Resource
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

    protected static ?string $model = Contract::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationLabel = 'Contracts';

    protected static ?string $navigationGroup = 'Subcontractor Management';

    protected static ?int $navigationSort = 6;

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            'Active' => 'success',
            'Draft' => 'gray',
            'Suspended' => 'warning',
            'Completed' => 'info',
            'Terminated' => 'danger',
            default => 'gray',
        };
    }

    public static function subcontractorProjectSchema(): array
    {
        return [
            Forms\Components\Select::make('subcontractor_id')
                ->label('Subcontractor')
                ->relationship('subcontractor', 'company_name')
                ->searchable()->preload()->required(),
            Forms\Components\Select::make('project_id')
                ->label('Project')
                ->relationship('project', 'name')
                ->searchable()->preload()->required(),
            Forms\Components\Select::make('site_id')
                ->label('Site')
                ->relationship('site', 'name')
                ->searchable()->preload(),
            Forms\Components\Select::make('tender_invitation_id')
                ->label('From Tender')
                ->relationship('tenderInvitation', 'tender_number')
                ->searchable()->preload(),
        ];
    }

    public static function scopeSummarySchema(): array
    {
        return [
            Forms\Components\Textarea::make('scope_summary')->rows(6)->columnSpanFull(),
        ];
    }

    public static function valueRetentionSchema(): array
    {
        return [
            Forms\Components\TextInput::make('contract_value')->numeric()->required()->prefix(session('currency', 'KES')),
            Forms\Components\TextInput::make('retention_percent')->numeric()->default(10)->suffix('%'),
        ];
    }

    public static function datesTermsSchema(): array
    {
        return [
            Forms\Components\DatePicker::make('start_date'),
            Forms\Components\DatePicker::make('completion_date'),
            Forms\Components\TextInput::make('payment_terms')->placeholder('e.g. Net 30'),
            Forms\Components\DatePicker::make('signed_date'),
            Forms\Components\Select::make('status')->options([
                'Draft' => 'Draft', 'Active' => 'Active', 'Suspended' => 'Suspended', 'Completed' => 'Completed', 'Terminated' => 'Terminated',
            ])->default('Draft')->required(),
        ];
    }

    public static function bondInsuranceSchema(): array
    {
        return [
            Forms\Components\TextInput::make('performance_bond_amount')->numeric()->prefix(session('currency', 'KES')),
            Forms\Components\TextInput::make('performance_bond_provider'),
            Forms\Components\Toggle::make('insurance_required')->default(true),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Subcontractor & Project')->schema(self::subcontractorProjectSchema())->columns(2),
                Forms\Components\Section::make('Scope Summary')->schema(self::scopeSummarySchema()),
                Forms\Components\Section::make('Value & Retention')->schema(self::valueRetentionSchema())->columns(2),
                Forms\Components\Section::make('Dates & Terms')->schema(self::datesTermsSchema())->columns(2),
                Forms\Components\Section::make('Bond & Insurance')->schema(self::bondInsuranceSchema())->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('contract_number')->label('Contract #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('subcontractor.company_name')->label('Subcontractor')->searchable(),
                Tables\Columns\TextColumn::make('project.name')->label('Project')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('contract_value')->money(session('currency', 'KES'))->sortable(),
                Tables\Columns\TextColumn::make('retention_percent')->suffix('%'),
                Tables\Columns\TextColumn::make('completion_date')->date()->sortable()
                    ->color(fn (Contract $record) => $record->is_expiring_soon ? 'danger' : null),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'Draft' => 'Draft', 'Active' => 'Active', 'Suspended' => 'Suspended', 'Completed' => 'Completed', 'Terminated' => 'Terminated',
                ]),
                Tables\Filters\SelectFilter::make('subcontractor_id')->label('Subcontractor')->relationship('subcontractor', 'company_name'),
                Tables\Filters\SelectFilter::make('project_id')->label('Project')->relationship('project', 'name'),
                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Expiring in 30 days')
                    ->query(fn ($query) => $query->whereBetween('completion_date', [now(), now()->addDays(30)])->whereIn('status', ['Active', 'Suspended'])),
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
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContracts::route('/'),
            'create' => Pages\CreateContract::route('/create'),
            'edit' => Pages\EditContract::route('/{record}/edit'),
            'view' => Pages\ViewContract::route('/{record}'),
        ];
    }
}
