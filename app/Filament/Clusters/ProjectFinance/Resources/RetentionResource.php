<?php

namespace App\Filament\Clusters\ProjectFinance\Resources;

use App\Filament\Clusters\ProjectFinance;
use App\Filament\Clusters\ProjectFinance\Resources\RetentionResource\Pages;
use App\Models\RetentionEntry;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class RetentionResource extends Resource
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

    protected static ?string $model = RetentionEntry::class;

    protected static ?string $cluster = ProjectFinance::class;

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?string $navigationLabel = 'Retention Management';

    protected static ?int $navigationSort = 10;

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            'Released' => 'success',
            'Partially Released' => 'warning',
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
                Forms\Components\Section::make()->schema([
                    Forms\Components\Select::make('project_id')
                        ->relationship('project', 'name')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('source_type')->options([
                        'progress_claim' => 'Progress Claim (Client)',
                        'payment_certificate' => 'Payment Certificate (Subcontractor)',
                    ])->required(),
                    Forms\Components\TextInput::make('percentage')->numeric()->suffix('%')->required(),
                    Forms\Components\TextInput::make('held_amount')->numeric()->prefix($currency)->required(),
                    Forms\Components\TextInput::make('released_amount')->numeric()->prefix($currency)->default(0),
                    Forms\Components\DatePicker::make('dlp_end_date')->label('Defects Liability Period End'),
                    Forms\Components\Select::make('release_status')->options([
                        'Held' => 'Held', 'Partially Released' => 'Partially Released', 'Released' => 'Released',
                    ])->default('Held')->required(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $currency = session('currency', 'KES');

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('project.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('source_type')->badge()->color('info')
                    ->formatStateUsing(fn (string $state) => str($state)->replace('_', ' ')->title()),
                Tables\Columns\TextColumn::make('percentage')->suffix('%'),
                Tables\Columns\TextColumn::make('held_amount')->money($currency)->sortable(),
                Tables\Columns\TextColumn::make('released_amount')->money($currency),
                Tables\Columns\TextColumn::make('outstanding')->money($currency)->sortable(),
                Tables\Columns\TextColumn::make('dlp_end_date')->date()->label('DLP End'),
                Tables\Columns\TextColumn::make('release_status')->badge()->color(fn (string $state) => static::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')->label('Project')->relationship('project', 'name'),
                Tables\Filters\SelectFilter::make('release_status')->options([
                    'Held' => 'Held', 'Partially Released' => 'Partially Released', 'Released' => 'Released',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('release')
                    ->label('Release')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->visible(fn (RetentionEntry $record) => $record->outstanding > 0 && Auth::user()?->can('project_finance.approve'))
                    ->form([
                        Forms\Components\TextInput::make('amount')->numeric()->prefix($currency)->required(),
                    ])
                    ->action(fn (RetentionEntry $record, array $data) => $record->release((float) $data['amount'])),
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
            'index' => Pages\ListRetentionEntries::route('/'),
            'create' => Pages\CreateRetentionEntry::route('/create'),
            'edit' => Pages\EditRetentionEntry::route('/{record}/edit'),
        ];
    }
}
