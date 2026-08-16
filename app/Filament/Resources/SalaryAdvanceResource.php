<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalaryAdvanceResource\Pages;
use App\Models\SalaryAdvance;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SalaryAdvanceResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction', 'quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'hr.payroll_access';
    }

    protected static ?string $model = SalaryAdvance::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Salary Advances';

    protected static ?string $navigationGroup = 'Labour & HR Management';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Salary Advance')->schema([
                    Forms\Components\Select::make('staff_id')
                        ->label('Employee')
                        ->relationship('staff', 'full_name')
                        ->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('amount')->numeric()->required()->prefix(session('currency', 'KES'))
                        ->reactive()
                        ->afterStateUpdated(fn (Forms\Set $set, $state) => $set('balance_remaining', $state)),
                    Forms\Components\TextInput::make('monthly_deduction')->numeric()->required()->prefix(session('currency', 'KES')),
                    Forms\Components\TextInput::make('balance_remaining')->numeric()->required()->prefix(session('currency', 'KES')),
                    Forms\Components\DatePicker::make('issued_date')->default(now()),
                    Forms\Components\Select::make('status')->options(['Active' => 'Active', 'Settled' => 'Settled'])->default('Active')->required(),
                    Forms\Components\Textarea::make('reason')->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('staff.full_name')->label('Employee')->searchable(),
                Tables\Columns\TextColumn::make('amount')->money(session('currency', 'KES')),
                Tables\Columns\TextColumn::make('monthly_deduction')->money(session('currency', 'KES')),
                Tables\Columns\TextColumn::make('balance_remaining')->money(session('currency', 'KES')),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => $state === 'Active' ? 'warning' : 'success'),
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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['staff']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalaryAdvances::route('/'),
            'create' => Pages\CreateSalaryAdvance::route('/create'),
            'edit' => Pages\EditSalaryAdvance::route('/{record}/edit'),
        ];
    }
}
