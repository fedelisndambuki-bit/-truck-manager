<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShiftSwapRequestResource\Pages;
use App\Models\ShiftSwapRequest;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ShiftSwapRequestResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction', 'quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'hr.view';
    }

    protected static ?string $model = ShiftSwapRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationLabel = 'Shift Swap Requests';

    protected static ?string $navigationGroup = 'Labour & HR Management';

    protected static ?int $navigationSort = 8;

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Swap Request')->schema([
                    Forms\Components\Select::make('from_staff_id')
                        ->label('From')
                        ->relationship('fromStaff', 'full_name')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('to_staff_id')
                        ->label('To')
                        ->relationship('toStaff', 'full_name')
                        ->searchable()->preload()->required(),
                    Forms\Components\DatePicker::make('date')->required(),
                    Forms\Components\Textarea::make('reason')->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fromStaff.full_name')->label('From'),
                Tables\Columns\TextColumn::make('toStaff.full_name')->label('To'),
                Tables\Columns\TextColumn::make('date')->date(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'Approved' => 'success',
                    'Rejected' => 'danger',
                    default => 'warning',
                }),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (ShiftSwapRequest $record) {
                        $record->update(['status' => 'Approved']);
                        Notification::make()->title('Swap approved')->success()->send();
                    })
                    ->visible(fn (ShiftSwapRequest $record) => $record->status === 'Pending'
                        && (Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('hr.approve'))),
                Tables\Actions\Action::make('reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (ShiftSwapRequest $record) {
                        $record->update(['status' => 'Rejected']);
                        Notification::make()->title('Swap rejected')->danger()->send();
                    })
                    ->visible(fn (ShiftSwapRequest $record) => $record->status === 'Pending'
                        && (Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('hr.approve'))),
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
        return parent::getEloquentQuery()->with(['fromStaff', 'toStaff']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShiftSwapRequests::route('/'),
            'create' => Pages\CreateShiftSwapRequest::route('/create'),
            'edit' => Pages\EditShiftSwapRequest::route('/{record}/edit'),
        ];
    }
}
