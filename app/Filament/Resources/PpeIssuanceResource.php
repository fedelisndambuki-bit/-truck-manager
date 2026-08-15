<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PpeIssuanceResource\Pages;
use App\Models\PpeIssuance;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PpeIssuanceResource extends Resource
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

    protected static ?string $model = PpeIssuance::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'PPE Compliance';

    protected static ?string $navigationGroup = 'Safety & Incident Management';

    protected static ?int $navigationSort = 110;

    public static function getItemOptions(): array
    {
        return [
            'Helmet' => 'Helmet',
            'Boots' => 'Boots',
            'Gloves' => 'Gloves',
            'Harness' => 'Harness',
            'Eye Protection' => 'Eye Protection',
            'Ear Protection' => 'Ear Protection',
            'Respirator' => 'Respirator',
            'Reflective Vest' => 'Reflective Vest',
            'Jacket' => 'Jacket',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('PPE Issuance')->schema([
                    Forms\Components\Select::make('staff_id')
                        ->label('Employee')
                        ->relationship('staff', 'full_name')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('item')->options(self::getItemOptions())->required(),
                    Forms\Components\TextInput::make('size'),
                    Forms\Components\TextInput::make('cost')->numeric()->prefix(session('currency', 'KES')),
                    Forms\Components\DatePicker::make('issue_date')->default(now()),
                    Forms\Components\DatePicker::make('replacement_due_date')->label('Expiry / Replacement Due'),
                    Forms\Components\Select::make('status')->options([
                        'Issued' => 'Issued', 'Due for Replacement' => 'Due for Replacement', 'Returned' => 'Returned',
                    ])->default('Issued')->required(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('staff.full_name')->label('Employee')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('item')->badge(),
                Tables\Columns\TextColumn::make('size')->placeholder('—'),
                Tables\Columns\TextColumn::make('issue_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('replacement_due_date')->label('Expiry / Due')->date()->placeholder('—')->sortable(),
                Tables\Columns\TextColumn::make('compliance_status')
                    ->label('Compliance')
                    ->state(function (PpeIssuance $record): string {
                        if ($record->status === 'Returned') {
                            return 'N/A';
                        }
                        if (! $record->replacement_due_date) {
                            return 'Compliant';
                        }

                        return match (true) {
                            $record->replacement_due_date->isPast() => 'Overdue',
                            $record->replacement_due_date->diffInDays(now()) <= 14 => 'Due Soon',
                            default => 'Compliant',
                        };
                    })
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Compliant' => 'success', 'Due Soon' => 'warning', 'Overdue' => 'danger', default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'Issued' => 'success',
                    'Due for Replacement' => 'warning',
                    'Returned' => 'gray',
                    default => 'gray',
                }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('item')->options(self::getItemOptions()),
                Tables\Filters\SelectFilter::make('status')->options([
                    'Issued' => 'Issued', 'Due for Replacement' => 'Due for Replacement', 'Returned' => 'Returned',
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('issue_date', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPpeIssuances::route('/'),
            'create' => Pages\CreatePpeIssuance::route('/create'),
            'edit' => Pages\EditPpeIssuance::route('/{record}/edit'),
        ];
    }
}
