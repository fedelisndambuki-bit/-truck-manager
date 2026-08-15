<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ComplianceRecordResource\Pages;
use App\Models\ComplianceRecord;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ComplianceRecordResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction', 'quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'safety.view';
    }

    protected static ?string $model = ComplianceRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationLabel = 'Compliance';

    protected static ?string $navigationGroup = 'Safety & Incident Management';

    protected static ?int $navigationSort = 140;

    public static function getStatusOptions(): array
    {
        return ['Compliant' => 'Compliant', 'Pending' => 'Pending', 'Expiring Soon' => 'Expiring Soon', 'Non-Compliant' => 'Non-Compliant', 'Expired' => 'Expired'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Compliance Record')->schema([
                    Forms\Components\Select::make('type')->options(array_combine(ComplianceRecord::TYPES, ComplianceRecord::TYPES))->required(),
                    Forms\Components\TextInput::make('title')->required()->columnSpan(2),
                    Forms\Components\Select::make('project_id')->label('Project')->relationship('project', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('site_id')->label('Site')->relationship('site', 'name')->searchable()->preload(),
                    Forms\Components\TextInput::make('reference_number'),
                    Forms\Components\TextInput::make('issuing_authority'),
                    Forms\Components\Select::make('responsible_staff_id')->label('Responsible Person')->relationship('responsibleStaff', 'full_name')->searchable()->preload(),
                    Forms\Components\DatePicker::make('issue_date'),
                    Forms\Components\DatePicker::make('expiry_date'),
                    Forms\Components\Select::make('status')->options(self::getStatusOptions())->default('Compliant')->required(),
                ])->columns(3),

                Forms\Components\Section::make('Notes & Document')->schema([
                    Forms\Components\Textarea::make('notes')->columnSpanFull()->rows(2),
                    Forms\Components\FileUpload::make('document_path')->label('Document')->disk('public')->directory('compliance-documents')->openable()->downloadable(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')->badge()->color('gray')->wrap(),
                Tables\Columns\TextColumn::make('title')->searchable()->limit(35),
                Tables\Columns\TextColumn::make('site.name')->label('Site')->toggleable()->placeholder('—'),
                Tables\Columns\TextColumn::make('issuing_authority')->toggleable()->placeholder('—'),
                Tables\Columns\TextColumn::make('expiry_date')->date()->sortable()->placeholder('—')
                    ->color(fn ($record) => $record->expiry_date && $record->expiry_date->isPast() ? 'danger' : null),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'Compliant' => 'success', 'Pending' => 'info', 'Expiring Soon' => 'warning',
                    'Non-Compliant', 'Expired' => 'danger', default => 'gray',
                }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->options(array_combine(ComplianceRecord::TYPES, ComplianceRecord::TYPES)),
                Tables\Filters\SelectFilter::make('status')->options(self::getStatusOptions()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->striped()
            ->defaultSort('expiry_date', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListComplianceRecords::route('/'),
            'create' => Pages\CreateComplianceRecord::route('/create'),
            'edit' => Pages\EditComplianceRecord::route('/{record}/edit'),
        ];
    }
}
