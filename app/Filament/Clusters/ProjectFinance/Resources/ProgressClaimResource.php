<?php

namespace App\Filament\Clusters\ProjectFinance\Resources;

use App\Filament\Clusters\ProjectFinance;
use App\Filament\Clusters\ProjectFinance\Resources\ProgressClaimResource\Pages;
use App\Models\ProgressClaim;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ProgressClaimResource extends Resource
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

    protected static ?string $model = ProgressClaim::class;

    protected static ?string $cluster = ProjectFinance::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationLabel = 'Progress Claims';

    protected static ?int $navigationSort = 9;

    protected static ?string $recordTitleAttribute = 'claim_number';

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            'Certified', 'Paid' => 'success',
            'Submitted' => 'warning',
            'Rejected' => 'danger',
            default => 'gray',
        };
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['project']);
    }

    public static function form(Form $form): Form
    {
        $currency = session('currency', 'KES');

        return $form
            ->schema([
                Forms\Components\Section::make('Claim Details')->schema([
                    Forms\Components\Select::make('project_id')
                        ->relationship('project', 'name')
                        ->searchable()->preload()->required(),
                    Forms\Components\DatePicker::make('period_start'),
                    Forms\Components\DatePicker::make('period_end'),
                    Forms\Components\Select::make('status')->options([
                        'Draft' => 'Draft', 'Submitted' => 'Submitted', 'Certified' => 'Certified',
                        'Paid' => 'Paid', 'Rejected' => 'Rejected',
                    ])->default('Draft')->required(),
                    Forms\Components\Select::make('submitted_by_id')
                        ->label('Submitted By')
                        ->relationship('submittedBy', 'full_name')
                        ->searchable()->preload(),
                ])->columns(2),
                Forms\Components\Section::make('Amounts')->schema([
                    Forms\Components\TextInput::make('previous_certified_amount')->numeric()->prefix($currency)->default(0),
                    Forms\Components\TextInput::make('work_completed_amount')->numeric()->prefix($currency)->required(),
                    Forms\Components\TextInput::make('amount_claimed')->numeric()->prefix($currency)->required()->live(),
                    Forms\Components\TextInput::make('retention_percent')->numeric()->suffix('%')->default(10),
                    Forms\Components\TextInput::make('vat_percent')->numeric()->suffix('%')->default(16),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $currency = session('currency', 'KES');

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('claim_number')->label('Claim #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('project.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('period_start')->date()->label('Period'),
                Tables\Columns\TextColumn::make('period_end')->date()->label('To'),
                Tables\Columns\TextColumn::make('amount_claimed')->money($currency)->sortable(),
                Tables\Columns\TextColumn::make('retention_amount')->money($currency)->toggleable(),
                Tables\Columns\TextColumn::make('vat_amount')->money($currency)->toggleable(),
                Tables\Columns\TextColumn::make('certified_amount')->money($currency)->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => static::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')->label('Project')->relationship('project', 'name'),
                Tables\Filters\SelectFilter::make('status')->options([
                    'Draft' => 'Draft', 'Submitted' => 'Submitted', 'Certified' => 'Certified',
                    'Paid' => 'Paid', 'Rejected' => 'Rejected',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('certify')
                    ->label('Certify')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (ProgressClaim $record) => $record->status === 'Submitted' && Auth::user()?->can('project_finance.approve'))
                    ->requiresConfirmation()
                    ->action(fn (ProgressClaim $record) => $record->certify()),
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
            'index' => Pages\ListProgressClaims::route('/'),
            'create' => Pages\CreateProgressClaim::route('/create'),
            'edit' => Pages\EditProgressClaim::route('/{record}/edit'),
        ];
    }
}
