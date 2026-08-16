<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubcontractorPrequalificationResource\Pages;
use App\Models\SubcontractorPrequalification;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SubcontractorPrequalificationResource extends Resource
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

    protected static ?string $model = SubcontractorPrequalification::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Prequalification';

    protected static ?string $navigationGroup = 'Subcontractor Management';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Prequalification Assessment')->schema([
                    Forms\Components\Select::make('subcontractor_id')
                        ->label('Subcontractor')
                        ->relationship('subcontractor', 'company_name')
                        ->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('score')->numeric()->minValue(0)->maxValue(100)->suffix('/ 100'),
                    Forms\Components\Toggle::make('insurance_verified'),
                    Forms\Components\Select::make('approval_status')->options([
                        'Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected',
                    ])->default('Pending')->required(),
                ])->columns(2),
                Forms\Components\Section::make('Capacity Assessment')->schema([
                    Forms\Components\Textarea::make('financial_capacity_notes')->label('Financial Capacity'),
                    Forms\Components\Textarea::make('technical_capacity_notes')->label('Technical Capacity'),
                    Forms\Components\Textarea::make('equipment_notes')->label('Equipment'),
                    Forms\Components\Textarea::make('past_projects_notes')->label('Past Projects'),
                    Forms\Components\Textarea::make('references_notes')->label('References'),
                    Forms\Components\Textarea::make('certifications_notes')->label('Certifications'),
                ])->columns(2),
                Forms\Components\Section::make('Compliance Assessment')->schema([
                    Forms\Components\Textarea::make('safety_record_notes')->label('Safety Record'),
                    Forms\Components\Textarea::make('quality_certifications_notes')->label('Quality Certifications'),
                    Forms\Components\Textarea::make('legal_compliance_notes')->label('Legal Compliance'),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('subcontractor.company_name')->label('Subcontractor')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('score')->suffix(' / 100')->placeholder('—'),
                Tables\Columns\IconColumn::make('insurance_verified')->boolean(),
                Tables\Columns\TextColumn::make('approval_status')->label('Status')->badge()->color(fn (string $state) => match ($state) {
                    'Approved' => 'success', 'Rejected' => 'danger', default => 'warning',
                }),
                Tables\Columns\TextColumn::make('approvedBy.full_name')->label('Approved By')->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')->label('Assessed On')->date()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('approval_status')->options([
                    'Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (SubcontractorPrequalification $record) {
                        $record->update(['approval_status' => 'Approved', 'approved_by_id' => null, 'approved_at' => now()]);
                        $record->subcontractor->update(['status' => 'Prequalified']);
                        Notification::make()->title('Subcontractor prequalified')->success()->send();
                    })
                    ->visible(fn (SubcontractorPrequalification $record) => $record->approval_status === 'Pending'
                        && (Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('subcontractors.approve'))),
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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['subcontractor', 'approvedBy']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubcontractorPrequalifications::route('/'),
            'create' => Pages\CreateSubcontractorPrequalification::route('/create'),
            'edit' => Pages\EditSubcontractorPrequalification::route('/{record}/edit'),
        ];
    }
}
