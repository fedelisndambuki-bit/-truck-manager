<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BidSubmissionResource\Pages;
use App\Models\BidSubmission;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class BidSubmissionResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'subcontractors.view';
    }

    protected static ?string $model = BidSubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationLabel = 'Bid Evaluation';

    protected static ?string $navigationGroup = 'Subcontractor Management';

    protected static ?int $navigationSort = 5;

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            'Awarded' => 'success',
            'Shortlisted' => 'info',
            'Rejected' => 'danger',
            'Under Review' => 'warning',
            default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Bid Details')->schema([
                    Forms\Components\Select::make('tender_invitation_id')
                        ->label('Tender')
                        ->relationship('tenderInvitation', 'tender_number')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('subcontractor_id')
                        ->label('Subcontractor')
                        ->relationship('subcontractor', 'company_name')
                        ->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('bid_amount')->numeric()->required()->prefix(session('currency', 'KES')),
                    Forms\Components\DatePicker::make('submission_date')->default(now()),
                    Forms\Components\TextInput::make('technical_score')->numeric()->minValue(0)->maxValue(100)->suffix('/ 100'),
                    Forms\Components\TextInput::make('commercial_score')->numeric()->minValue(0)->maxValue(100)->suffix('/ 100'),
                    Forms\Components\Select::make('status')->options([
                        'Submitted' => 'Submitted', 'Under Review' => 'Under Review', 'Shortlisted' => 'Shortlisted',
                        'Awarded' => 'Awarded', 'Rejected' => 'Rejected',
                    ])->default('Submitted')->required(),
                    Forms\Components\Textarea::make('notes')->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenderInvitation.tender_number')->label('Tender #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('subcontractor.company_name')->label('Subcontractor')->searchable(),
                Tables\Columns\TextColumn::make('bid_amount')->money(session('currency', 'KES'))->sortable(),
                Tables\Columns\TextColumn::make('technical_score')->suffix(' / 100')->placeholder('—'),
                Tables\Columns\TextColumn::make('commercial_score')->suffix(' / 100')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tender_invitation_id')->label('Tender')->relationship('tenderInvitation', 'tender_number'),
                Tables\Filters\SelectFilter::make('status')->options([
                    'Submitted' => 'Submitted', 'Under Review' => 'Under Review', 'Shortlisted' => 'Shortlisted',
                    'Awarded' => 'Awarded', 'Rejected' => 'Rejected',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('shortlist')
                    ->icon('heroicon-o-star')
                    ->color('info')
                    ->action(fn (BidSubmission $record) => $record->update(['status' => 'Shortlisted']))
                    ->visible(fn (BidSubmission $record) => in_array($record->status, ['Submitted', 'Under Review'])
                        && (Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('subcontractors.approve'))),
                Tables\Actions\Action::make('award')
                    ->icon('heroicon-o-trophy')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('This awards the tender to this bid and automatically rejects all other bids on the same tender.')
                    ->action(function (BidSubmission $record) {
                        $record->award();
                        Notification::make()->title('Tender awarded to ' . $record->subcontractor->company_name)->success()->send();
                    })
                    ->visible(fn (BidSubmission $record) => $record->status !== 'Awarded'
                        && (Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('subcontractors.award'))),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('bid_amount')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBidSubmissions::route('/'),
            'create' => Pages\CreateBidSubmission::route('/create'),
            'edit' => Pages\EditBidSubmission::route('/{record}/edit'),
        ];
    }
}
