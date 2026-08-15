<?php

namespace App\Filament\Clusters\BlastManagement\Resources;

use App\Filament\Clusters\BlastManagement;
use App\Filament\Clusters\BlastManagement\Resources\ExplosiveIssueResource\Pages;
use App\Models\ExplosiveIssue;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExplosiveIssueResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = BlastManagement::class;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'blast.view';
    }

    protected static ?string $model = ExplosiveIssue::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationLabel = 'Explosive Inventory';

    protected static ?int $navigationSort = 4;

    public static function getApprovalStatusOptions(): array
    {
        return array_combine(ExplosiveIssue::APPROVAL_STATUSES, ExplosiveIssue::APPROVAL_STATUSES);
    }

    public static function getApprovalStatusColor(string $state): string
    {
        return match ($state) {
            'Approved' => 'success',
            'Pending' => 'warning',
            'Rejected' => 'danger',
            default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Explosive Issue Details')->schema([
                    Forms\Components\Select::make('blast_plan_id')->label('Blast Plan')
                        ->relationship('blastPlan', 'blast_number')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->blast_number ?? ('Blast #' . $record->id))
                        ->searchable()->preload()
                        ->helperText('Leave blank if stock has not yet been assigned to a specific blast.'),
                    Forms\Components\TextInput::make('explosive_type')->required(),
                    Forms\Components\TextInput::make('batch_number'),
                    Forms\Components\TextInput::make('supplier'),
                    Forms\Components\TextInput::make('storage_magazine'),
                    Forms\Components\TextInput::make('quantity_issued')->numeric()->suffix('kg')->required()->live(onBlur: true),
                    Forms\Components\TextInput::make('quantity_used')->numeric()->suffix('kg')->live(onBlur: true),
                    Forms\Components\TextInput::make('remaining_quantity')->numeric()->suffix('kg')->disabled()->dehydrated(false)->placeholder('Computed automatically'),
                    Forms\Components\TextInput::make('detonator_type'),
                    Forms\Components\TextInput::make('initiation_system'),
                    Forms\Components\DatePicker::make('issue_date')->default(now()),
                    Forms\Components\DatePicker::make('expiry_date'),
                    Forms\Components\Select::make('approval_status')->options(self::getApprovalStatusOptions())->default('Pending')->required(),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('explosive_type')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('batch_number')->placeholder('—'),
                Tables\Columns\TextColumn::make('blastPlan.blast_number')->label('Blast #')->placeholder('—'),
                Tables\Columns\TextColumn::make('storage_magazine')->placeholder('—'),
                Tables\Columns\TextColumn::make('quantity_issued')->numeric()->suffix(' kg'),
                Tables\Columns\TextColumn::make('remaining_quantity')->numeric()->suffix(' kg')
                    ->color(fn (ExplosiveIssue $record): string => $record->remaining_quantity <= 0 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('expiry_date')->date()->placeholder('—'),
                Tables\Columns\TextColumn::make('approval_status')->badge()->color(fn (string $state): string => self::getApprovalStatusColor($state)),
            ])
            ->defaultSort('issue_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('approval_status')->options(self::getApprovalStatusOptions()),
            ])
            ->actions([
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
            'index' => Pages\ListExplosiveIssues::route('/'),
            'create' => Pages\CreateExplosiveIssue::route('/create'),
            'edit' => Pages\EditExplosiveIssue::route('/{record}/edit'),
        ];
    }
}
