<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\CompanyResource\Pages;
use App\Models\Company;
use App\Models\SystemLog;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Main';

    protected static ?string $navigationLabel = 'Client Management';

    protected static ?string $modelLabel = 'Client';

    public static function form(Form $form): Form
    {
        return $form->schema([
            // ── TOP KPI ROW ──
            Forms\Components\Grid::make(3)
                ->schema([
                    Forms\Components\Placeholder::make('active_clients')
                        ->label('')
                        ->content(new \Illuminate\Support\HtmlString('
                            <div class="flex items-center gap-3 p-3 bg-white border border-gray-100 rounded-xl shadow-sm">
                                <div class="p-2 bg-green-50 rounded-lg"><svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div>
                                <div><p class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">Active Clients</p><p class="text-lg font-black text-gray-900">' . Company::where('status', 'active')->count() . '</p></div>
                            </div>
                        ')),
                    Forms\Components\Placeholder::make('trial_clients')
                        ->label('')
                        ->content(new \Illuminate\Support\HtmlString('
                            <div class="flex items-center gap-3 p-3 bg-white border border-gray-100 rounded-xl shadow-sm">
                                <div class="p-2 bg-amber-50 rounded-lg"><svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div>
                                <div><p class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">On Trial</p><p class="text-lg font-black text-gray-900">' . Company::where('status', 'trial')->count() . '</p></div>
                            </div>
                        ')),
                    Forms\Components\Placeholder::make('suspended_clients')
                        ->label('')
                        ->content(new \Illuminate\Support\HtmlString('
                            <div class="flex items-center gap-3 p-3 bg-white border border-gray-100 rounded-xl shadow-sm">
                                <div class="p-2 bg-red-50 rounded-lg"><svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div>
                                <div><p class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">Suspended</p><p class="text-lg font-black text-gray-900">' . Company::where('status', 'suspended')->count() . '</p></div>
                            </div>
                        ')),
                ])->columnSpanFull(),

            Forms\Components\Split::make([
                // ── LEFT SIDEBAR: PROFILE & CONTROLS ──
                Forms\Components\Section::make([
                    // Client Identity
                    Forms\Components\View::make('filament.super-admin.components.client-profile-header'),
                    
                    // Plan Selection
                    Forms\Components\Section::make('Subscription Plan')
                        ->description('Active billing tier')
                        ->collapsible()
                        ->schema([
                            Forms\Components\Select::make('plan_id')
                                ->relationship('plan', 'name')
                                ->getOptionLabelFromRecordUsing(fn ($record) => $record->name ?? ("Plan #{$record->id}"))
                                ->live()
                                ->afterStateUpdated(fn (Company $record, $state) => $record->customModules()->detach())
                                ->required()
                                ->helperText('Overrides the company\'s plan directly (e.g. comping a plan). For a real billing event — a payment, a renewal, an expiry — use the Subscriptions page instead so it\'s properly recorded.'),
                            Forms\Components\Select::make('subscription_id')
                                ->label('Current Subscription')
                                ->relationship('currentSubscription', 'plan_name')
                                ->searchable()
                                ->preload()
                                ->helperText('The billing record backing this company\'s access. Changing this only re-points which Subscription row counts as "current" — it does not change the plan above.'),
                        ]),

                    // Account Status
                    Forms\Components\Section::make('Account Status')
                        ->schema([
                            Forms\Components\Select::make('status')
                                ->options([
                                    'active' => 'Active',
                                    'suspended' => 'Suspended',
                                    'trial' => 'Trial',
                                ])
                                ->required()
                                ->native(false),
                            Forms\Components\Select::make('industry')
                                ->options(\App\Enums\CompanyIndustry::class)
                                ->required()
                                ->native(false)
                                ->helperText('Controls which dashboard and navigation this company sees.'),
                            Forms\Components\DateTimePicker::make('trial_ends_at')
                                ->hidden(fn (Get $get) => $get('status') !== 'trial'),
                        ]),

                    // Danger Zone
                    Forms\Components\Section::make('Danger Zone')
                        ->schema([
                            Forms\Components\Placeholder::make('remove_client')
                                ->label('')
                                ->content(new \Illuminate\Support\HtmlString('
                                    <button type="button" onclick="confirm(\'Remove this client?\') || event.stopImmediatePropagation()" 
                                            class="w-full text-xs font-bold text-red-600 bg-red-50 py-2 rounded-lg border border-red-100 hover:bg-red-100 transition">
                                        REMOVE CLIENT
                                    </button>
                                ')),
                        ]),
                ])->grow(false)->maxWidth('280px'),

                // ── RIGHT PANEL: MODULE ACCESS & OVERVIEW ──
                Forms\Components\Tabs::make('Client Details')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Module Access')
                            ->icon('heroicon-o-cpu-chip')
                            ->schema([
                                Forms\Components\View::make('filament.super-admin.components.module-access-tiles'),
                            ]),
                        Forms\Components\Tabs\Tab::make('Client Overview')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('name')->required()->maxLength(255),
                                        Forms\Components\TextInput::make('email')->email()->maxLength(255),
                                        Forms\Components\TextInput::make('phone')->maxLength(50),
                                        Forms\Components\TextInput::make('tax_id')->label('Tax ID / KRA PIN'),
                                        Forms\Components\Textarea::make('address')->columnSpanFull(),
                                    ]),
                            ]),
                    ])->grow(true),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Client')
                    ->searchable()
                    ->sortable()
                    ->html()
                    ->formatStateUsing(fn ($state, $record) => '
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-gray-100 text-[#2f4f4f] font-bold text-xs border border-gray-200">' . strtoupper(substr($state ?: 'CL', 0, 2)) . '</div>
                            <div>
                                <div class="text-sm font-bold text-gray-900">' . ($state ?: 'Unnamed Client') . '</div>
                                <div class="text-[10px] text-gray-400 font-medium uppercase tracking-tighter">Since ' . ($record->created_at ? $record->created_at->format('M Y') : 'N/A') . '</div>
                            </div>
                        </div>
                    '),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->size('sm')
                    ->weight('medium')
                    ->color('gray')
                    ->placeholder('no-email@platform.com'),
                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plan')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Starter' => 'gray',
                        'Growth' => 'info',
                        'Enterprise' => 'success',
                        default => 'gray',
                    })
                    ->placeholder('No Plan'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'trial' => 'warning',
                        'suspended' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make('industry')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => \App\Enums\CompanyIndustry::from($state)->getLabel()),
                Tables\Columns\TextColumn::make('currentSubscription.price')
                    ->label('MRR')
                    ->money()
                    ->sortable()
                    ->weight('black')
                    ->color('primary')
                    ->placeholder('0.00'),
                Tables\Columns\TextColumn::make('modules_count')
                    ->label('Modules')
                    ->tooltip('What the plan advertises as included — informational only. Actual access is controlled by each company\'s own Roles & Permissions, not this count.')
                    ->getStateUsing(fn (Company $record) => $record->getEffectiveModules()->count())
                    ->html()
                    ->formatStateUsing(fn ($state, $record) => '
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-gray-600">' . $state . '/' . \App\Models\Module::count() . '</span>
                            ' . ($record->customModules()->exists() ? '<span class="px-1.5 py-0.5 rounded text-[8px] font-black bg-amber-100 text-amber-700">CUSTOM</span>' : '') . '
                        </div>
                    '),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Manage')
                    ->button()
                    ->icon('heroicon-m-arrow-right-circle')
                    ->color('primary'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}
