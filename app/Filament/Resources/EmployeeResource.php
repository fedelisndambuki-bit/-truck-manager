<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;

use App\Traits\HasModuleAccess;

class EmployeeResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'driver-management';
    }

    protected static ?string $model = Employee::class;

    protected static ?string $navigationLabel = 'Drivers';

    // Distinct icon: steering wheel / user for drivers
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationGroup = 'General';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Driver Profile')
                    ->schema([
                        Forms\Components\FileUpload::make('profile_photo')
                            ->image()
                            ->avatar()
                            ->disk('public')
                            ->visibility('public')
                            ->directory('drivers')
                            ->columnSpan(1),
                        Forms\Components\Group::make()->schema([
                            Forms\Components\TextInput::make('full_name')->required(),
                            Forms\Components\Select::make('status')
                                ->options(['Active' => 'Active', 'On Leave' => 'On Leave', 'Suspended' => 'Suspended'])
                                ->required()->native(false),
                        ])->columnSpan(2),
                    ])->columns(3),

                Forms\Components\Section::make('Contact & Identity')
                    ->schema([
                        Forms\Components\TextInput::make('phone_number')
                            ->tel()
                            ->required()
                            ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule) => $rule->where('company_id', auth()->user()->company_id)),
                        Forms\Components\TextInput::make('email')->email(),
                        Forms\Components\TextInput::make('national_id')->label('National ID / Passport')->required(),
                        Forms\Components\TextInput::make('address')->columnSpanFull(),
                    ])->columns(3),

                Forms\Components\Section::make('License & Job Details')
                    ->schema([
                        Forms\Components\TextInput::make('license_number')->required(),
                        Forms\Components\DatePicker::make('license_expiry')->required(),
                        Forms\Components\Select::make('category')
                            ->options(['Local' => 'Local', 'Long Distance' => 'Long Distance'])->required(),
                        Forms\Components\TextInput::make('tax_code')
                            ->label('Tax Code')
                            ->placeholder('e.g. 1250L')
                            ->maxLength(20),
                        Forms\Components\Select::make('truck_id')
                            ->relationship('truck', 'plate_number')->label('Assigned Truck'),
                        Forms\Components\DatePicker::make('date_hired')->default(now())->required(),
                    ])->columns(3),

                Forms\Components\Section::make('Emergency Contact')
                    ->schema([
                        Forms\Components\TextInput::make('emergency_contact_name')->required(),
                        Forms\Components\TextInput::make('emergency_contact_phone')->tel()->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('profile_photo')
                    ->label('Photo')
                    ->circular()
                    ->height(40)
                    ->getStateUsing(fn (Employee $record): ?string => self::publicAssetUrl($record->profile_photo))
                    ->defaultImageUrl(fn (Employee $record): string => 'https://ui-avatars.com/api/?name='.urlencode($record->full_name)),
                Tables\Columns\TextColumn::make('full_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('truck.plate_number')->label('Assigned Truck'),

                // --- LICENSE EXPIRY NOTIFICATION LOGIC ---
                Tables\Columns\TextColumn::make('license_expiry')
                    ->date()
                    ->sortable()
                    ->label('License Exp')
                    ->color(fn (Employee $record): string => match (true) {
                        $record->license_expiry <= now() => 'danger', // Red if expired
                        $record->license_expiry <= now()->addDays(30) => 'warning', // Orange if within 30 days
                        default => 'success',
                    })
                    ->description(fn (Employee $record): string => $record->license_expiry <= now() ? '⚠️ EXPIRED' :
                        ($record->license_expiry <= now()->addDays(30) ? 'Expires Soon' : '')
                    ),

                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match (Str::title($state)) {
                        'Active' => 'success',
                        'On Leave' => 'warning',
                        'Suspended' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                // --- FILTER FOR EXPIRING LICENSES ---
                Tables\Filters\Filter::make('license_expiring_soon')
                    ->label('Expiring (Next 30 Days)')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('license_expiry', [
                        now(),
                        now()->addDays(30),
                    ])),

                Tables\Filters\SelectFilter::make('status')
                    ->options(['Active' => 'Active', 'Suspended' => 'Suspended']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])->striped();
    }

    private static function publicAssetUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $normalized = ltrim($path, '/');
        if (Str::startsWith($normalized, 'storage/')) {
            $normalized = Str::after($normalized, 'storage/');
        }

        return Storage::disk('public')->url($normalized);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery()->with(['truck']);
        $user  = auth()->user();
        if ($user) {
            $query->where('company_id', $user->company_id);
        }
        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
