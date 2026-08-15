<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\PlanResource\Pages;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Plans';

    /**
     * subscriptions.plan_id is nullOnDelete, so deleting a Plan that real
     * subscriptions reference would silently orphan those rows instead of
     * failing loudly. Block it instead — reassign or archive first.
     */
    public static function canDelete($record): bool
    {
        return ! $record->subscriptions()->exists();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\Toggle::make('is_featured')
                ->label('Most Popular')
                ->helperText('Shows the "Most Popular" badge and highlighted styling on the landing page pricing card.'),
            Forms\Components\Toggle::make('is_default')
                ->label('Default trial plan')
                ->helperText('The plan every new company is attached to when they register and start their free trial. Turning this on for a plan turns it off for every other plan.'),
            Forms\Components\TextInput::make('price')->numeric()->required()->prefix(session('currency', 'KES')),
            Forms\Components\TextInput::make('truck_limit')->numeric()->default(0)->required(),
            Forms\Components\TextInput::make('driver_limit')->numeric()->default(0)->required(),
            Forms\Components\TextInput::make('ai_requests_limit')->numeric()->default(0)->required(),
            Forms\Components\Section::make('Landing Page Display')
                ->description('Everything shown on this plan\'s public pricing card.')
                ->schema([
                    Forms\Components\TextInput::make('tagline')
                        ->label('Short description')
                        ->helperText('e.g. "For growing businesses that need deeper operational control."')
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('access_level_label')
                        ->label('Access level phrase')
                        ->helperText('e.g. "Expanded operational access"')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('cta_label')
                        ->label('Button text')
                        ->helperText('e.g. "Start Growing"')
                        ->maxLength(255),
                    Forms\Components\KeyValue::make('features')
                        ->label('Feature bullets')
                        ->keyLabel('Feature text')
                        ->valueLabel('Show on landing page?')
                        ->helperText('Each key is one bullet on the pricing card. Set the value to "yes" to show it, anything else to hide it.')
                        ->columnSpanFull(),
                ])->columns(2),
            Forms\Components\Section::make('Included Modules (Marketing Display Only)')
                ->description('What this plan advertises as included — shown on the landing page and the module count on the Companies list. This does NOT control what a company\'s users can actually access: that\'s entirely determined by the Roles & Permissions each company configures for its own users (User Management → Roles), independent of plan. Checking a module here never grants or revokes real access.')
                ->schema([
                    Forms\Components\CheckboxList::make('modules')
                        ->relationship('modules', 'name')
                        ->bulkToggleable()
                        ->searchable()
                        ->columns(3),
                ]),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
            Tables\Columns\IconColumn::make('is_featured')->label('Most Popular')->boolean(),
            Tables\Columns\IconColumn::make('is_default')->label('Default Trial Plan')->boolean(),
            Tables\Columns\TextColumn::make('price')->money()->sortable(),
            Tables\Columns\TextColumn::make('truck_limit')->label('Truck Limit'),
            Tables\Columns\TextColumn::make('driver_limit')->label('Driver Limit'),
            Tables\Columns\TextColumn::make('ai_requests_limit')->label('AI Limit'),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make()
                ->visible(fn (Plan $record): bool => ! $record->subscriptions()->exists()),
        ])->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make()
                    // The stock bulk delete has no per-record authorization
                    // hook, so it would otherwise happily orphan
                    // subscriptions.plan_id for any referenced Plan — split
                    // the selection and only delete the ones that are safe.
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                        $blocked = $records->filter(fn (Plan $plan) => $plan->subscriptions()->exists());
                        $deletable = $records->diff($blocked);

                        $deletable->each(fn (Plan $plan) => $plan->delete());

                        if ($blocked->isNotEmpty()) {
                            \Filament\Notifications\Notification::make()
                                ->title($blocked->count() . ' plan(s) skipped')
                                ->body('Plans with real subscriptions attached (' . $blocked->pluck('name')->join(', ') . ') were not deleted.')
                                ->warning()
                                ->send();
                        }
                    }),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}
