<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Models\Attendance;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AttendanceResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction', 'quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'hr.attendance_access';
    }

    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-finger-print';

    protected static ?string $navigationGroup = 'Labour & HR Management';

    protected static ?int $navigationSort = 6;

    public static function getCheckInMethodOptions(): array
    {
        return [
            'Manual' => 'Manual',
            'GPS' => 'GPS',
            'QR' => 'QR Code',
            'Biometric' => 'Biometric',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Attendance Record')->schema([
                    Forms\Components\Select::make('employee_id')
                        ->label('Employee')
                        ->relationship('employee', 'full_name')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('site_id')
                        ->label('Site')
                        ->relationship('site', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('shift_id')
                        ->label('Shift')
                        ->relationship('shift', 'name')
                        ->searchable()->preload(),
                    Forms\Components\DatePicker::make('date')->default(now())->required(),
                    Forms\Components\DateTimePicker::make('check_in_at'),
                    Forms\Components\DateTimePicker::make('check_out_at'),
                    Forms\Components\Select::make('check_in_method')->options(self::getCheckInMethodOptions())->default('Manual'),
                    Forms\Components\Select::make('status')->options([
                        'Present' => 'Present',
                        'Absent' => 'Absent',
                    ])->default('Present')->required(),
                    Forms\Components\Toggle::make('late'),
                    Forms\Components\Toggle::make('early_departure'),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')->date()->sortable(),
                Tables\Columns\TextColumn::make('employee.full_name')->label('Employee')->searchable(),
                Tables\Columns\TextColumn::make('site.name')->label('Site')->toggleable(),
                Tables\Columns\TextColumn::make('shift.name')->label('Shift')->toggleable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => $state === 'Present' ? 'success' : 'danger'),
                Tables\Columns\IconColumn::make('late')->boolean()->label('Late'),
                Tables\Columns\IconColumn::make('early_departure')->boolean()->label('Early Departure'),
                Tables\Columns\TextColumn::make('check_in_at')->dateTime('H:i')->label('Check In'),
                Tables\Columns\TextColumn::make('check_out_at')->dateTime('H:i')->label('Check Out'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(['Present' => 'Present', 'Absent' => 'Absent']),
                Tables\Filters\SelectFilter::make('site_id')->label('Site')->relationship('site', 'name'),
                Tables\Filters\Filter::make('date')
                    ->form([Forms\Components\DatePicker::make('date')])
                    ->query(fn (\Illuminate\Database\Eloquent\Builder $query, array $data) => $query->when($data['date'] ?? null, fn ($q, $date) => $q->whereDate('date', $date))),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
