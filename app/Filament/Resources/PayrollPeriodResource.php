<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayrollPeriodResource\Pages;
use App\Models\PayrollPeriod;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PayrollPeriodResource extends Resource
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

    protected static ?string $model = PayrollPeriod::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Payroll';

    protected static ?string $navigationGroup = 'Labour & HR Management';

    protected static ?int $navigationSort = 9;

    public static function getMonthOptions(): array
    {
        return [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];
    }

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            'Draft' => 'gray',
            'Processing' => 'warning',
            'Approved' => 'info',
            'Paid' => 'success',
            default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payroll Period')->schema([
                    Forms\Components\Select::make('month')->options(self::getMonthOptions())->default(now()->month)->required(),
                    Forms\Components\TextInput::make('year')->numeric()->default(now()->year)->required(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('month')->formatStateUsing(fn (int $state) => self::getMonthOptions()[$state] ?? $state)->label('Month'),
                Tables\Columns\TextColumn::make('year'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => self::getStatusColor($state)),
                Tables\Columns\TextColumn::make('total_gross')->money(session('currency', 'KES'))->label('Total Gross'),
                Tables\Columns\TextColumn::make('total_deductions')->money(session('currency', 'KES'))->label('Total Deductions'),
                Tables\Columns\TextColumn::make('total_net')->money(session('currency', 'KES'))->label('Total Net'),
                Tables\Columns\TextColumn::make('payslips_count')->counts('payslips')->label('Payslips'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalIcon('heroicon-o-eye')
                    ->modalIconColor('gray'),
                Tables\Actions\Action::make('process')
                    ->label('Process Payroll')
                    ->icon('heroicon-o-play')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('This will generate payslips for every active employee, replacing any existing payslips for this period.')
                    ->action(fn (PayrollPeriod $record) => $record->process())
                    ->visible(fn (PayrollPeriod $record) => $record->status !== 'Paid'
                        && (Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('hr.payroll_access'))),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('year', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayrollPeriods::route('/'),
            'create' => Pages\CreatePayrollPeriod::route('/create'),
            'view' => Pages\ViewPayrollPeriod::route('/{record}'),
        ];
    }
}
