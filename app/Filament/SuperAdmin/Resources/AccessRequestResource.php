<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Enums\CompanyIndustry;
use App\Exceptions\AccessRequestStateConflictException;
use App\Filament\SuperAdmin\Resources\AccessRequestResource\Pages;
use App\Models\AccessRequest;
use App\Models\Plan;
use App\Services\AccessRequestApprovalService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AccessRequestResource extends Resource
{
    protected static ?string $model = AccessRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $navigationGroup = 'Access Requests';

    protected static ?string $navigationLabel = 'Access Requests';

    protected static ?int $navigationSort = 0;

    public static function canCreate(): bool
    {
        // Requests only ever originate from the public /request-access form.
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::whereIn('status', [
            AccessRequest::STATUS_PENDING_REVIEW,
            AccessRequest::STATUS_IN_REVIEW,
        ])->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Applicant')->schema([
                Forms\Components\TextInput::make('requester_name')->disabled(),
                Forms\Components\TextInput::make('requester_email')->disabled(),
                Forms\Components\TextInput::make('requester_phone')->disabled(),
            ])->columns(3),
            Forms\Components\Section::make('Organization')->schema([
                Forms\Components\TextInput::make('organization_name')->disabled(),
                Forms\Components\TextInput::make('organization_country')->disabled(),
                Forms\Components\TextInput::make('organization_type')->disabled(),
                Forms\Components\TextInput::make('organization_size')->disabled(),
                Forms\Components\Textarea::make('operation_description')->disabled()->columnSpanFull(),
            ])->columns(2),
            Forms\Components\Section::make('Review')->schema([
                Forms\Components\TextInput::make('status')->disabled(),
                Forms\Components\Textarea::make('rejection_reason')->disabled(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('requester_name')->label('Applicant')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('requester_email')->label('Email')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('organization_name')->label('Organization')->searchable(),
                Tables\Columns\TextColumn::make('organization_type')->label('Type'),
                Tables\Columns\TextColumn::make('organization_country')->label('Country')->toggleable(),
                Tables\Columns\TextColumn::make('organization_size')->label('Size')->toggleable(),
                Tables\Columns\IconColumn::make('domain_is_risky')
                    ->label('Risk')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->tooltip(fn (AccessRequest $record) => $record->domain_risk_reason),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => AccessRequest::STATUS_LABELS[$state] ?? $state)
                    ->colors([
                        'gray' => AccessRequest::STATUS_PENDING_EMAIL_VERIFICATION,
                        'info' => [AccessRequest::STATUS_EMAIL_VERIFIED, AccessRequest::STATUS_PENDING_REVIEW],
                        'warning' => [AccessRequest::STATUS_IN_REVIEW, AccessRequest::STATUS_ADDITIONAL_INFORMATION_REQUIRED],
                        'success' => AccessRequest::STATUS_APPROVED,
                        'danger' => [AccessRequest::STATUS_REJECTED, AccessRequest::STATUS_CANCELLED],
                    ]),
                Tables\Columns\TextColumn::make('created_at')->label('Submitted')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(AccessRequest::STATUS_LABELS),
                Tables\Filters\TernaryFilter::make('domain_is_risky')->label('Domain Risk Flagged'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('resend_verification')
                    ->label('Resend Verification Email')
                    ->icon('heroicon-o-envelope')
                    ->color('gray')
                    ->visible(fn (AccessRequest $record) => $record->status === AccessRequest::STATUS_PENDING_EMAIL_VERIFICATION)
                    ->requiresConfirmation()
                    ->modalDescription(fn (AccessRequest $record) => "Send a new verification link to {$record->requester_email}? The previous link will stop working.")
                    ->action(function (AccessRequest $record) {
                        static::handleServiceCall(function (AccessRequestApprovalService $service) use ($record) {
                            $service->resendVerification($record->id, auth()->user());

                            Notification::make()
                                ->title('Verification email resent')
                                ->body("A new verification link was sent to {$record->requester_email}.")
                                ->success()
                                ->send();
                        });
                    }),

                Tables\Actions\Action::make('start_review')
                    ->label('Start Review')
                    ->icon('heroicon-o-magnifying-glass')
                    ->visible(fn (AccessRequest $record) => $record->status === AccessRequest::STATUS_PENDING_REVIEW)
                    ->action(function (AccessRequest $record) {
                        static::handleServiceCall(fn (AccessRequestApprovalService $service) => $service->startReview($record->id, auth()->user()));
                    }),

                Tables\Actions\Action::make('request_more_info')
                    ->label('Request More Info')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('gray')
                    ->visible(fn (AccessRequest $record) => $record->status === AccessRequest::STATUS_IN_REVIEW)
                    ->form([
                        Forms\Components\Textarea::make('message')->label('What do you need from the applicant?')->required(),
                    ])
                    ->action(function (AccessRequest $record, array $data) {
                        static::handleServiceCall(fn (AccessRequestApprovalService $service) => $service->requestMoreInformation($record->id, auth()->user(), $data['message']));
                    }),

                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (AccessRequest $record) => $record->status === AccessRequest::STATUS_IN_REVIEW)
                    ->form(fn (AccessRequest $record) => [
                        Forms\Components\Select::make('plan_id')
                            ->label('Plan')
                            ->options(Plan::pluck('name', 'id'))
                            ->default(Plan::where('is_default', true)->value('id'))
                            ->required()
                            ->helperText('Selecting Enterprise requires sign-off from a second, different Super Admin before it finalizes.'),
                        Forms\Components\Select::make('industry')
                            ->label('Industry')
                            ->options(CompanyIndustry::class)
                            ->default(fn () => static::guessIndustry($record->organization_type))
                            ->required()
                            ->helperText('Confirm this matches what the applicant actually operates — the requested "Company Type" is a guide, not a guarantee.'),
                    ])
                    ->action(function (AccessRequest $record, array $data) {
                        static::handleServiceCall(function (AccessRequestApprovalService $service) use ($record, $data) {
                            $reviewer = auth()->user();

                            if ($service->requiresSecondApproval($data['plan_id'])) {
                                if (! $record->first_approved_by) {
                                    $service->recordFirstApproval($record->id, $reviewer, $data['plan_id']);

                                    Notification::make()
                                        ->title('First approval recorded')
                                        ->body('A second, different Super Admin must approve this Enterprise-tier request to finalize it.')
                                        ->warning()
                                        ->send();

                                    return;
                                }

                                if ((int) $record->first_approved_by === (int) $reviewer->id) {
                                    Notification::make()
                                        ->title('Second sign-off required')
                                        ->body('A different Super Admin must confirm this Enterprise-tier approval.')
                                        ->danger()
                                        ->send();

                                    return;
                                }
                            }

                            $service->approve($record->id, $reviewer, $data['plan_id'], $data['industry']);

                            Notification::make()->title('Access request approved')->success()->send();
                        });
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (AccessRequest $record) => in_array($record->status, [
                        AccessRequest::STATUS_IN_REVIEW,
                        AccessRequest::STATUS_ADDITIONAL_INFORMATION_REQUIRED,
                    ], true))
                    ->form([
                        Forms\Components\Textarea::make('reason')->label('Internal rejection reason (not shown to applicant)')->required(),
                    ])
                    ->requiresConfirmation()
                    ->action(function (AccessRequest $record, array $data) {
                        static::handleServiceCall(fn (AccessRequestApprovalService $service) => $service->reject($record->id, auth()->user(), $data['reason']));
                    }),
            ])
            ->bulkActions([]);
    }

    protected static function handleServiceCall(\Closure $callback): void
    {
        try {
            $callback(app(AccessRequestApprovalService::class));
        } catch (AccessRequestStateConflictException $e) {
            Notification::make()->title('Action failed')->body($e->getMessage())->danger()->send();
        }
    }

    protected static function guessIndustry(?string $organizationType): string
    {
        return match ($organizationType) {
            'Construction' => CompanyIndustry::Construction->value,
            'Quarry' => CompanyIndustry::Quarry->value,
            'Multi-Operation' => CompanyIndustry::MultiOperation->value,
            default => CompanyIndustry::Trucking->value,
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccessRequests::route('/'),
            'view' => Pages\ViewAccessRequest::route('/{record}'),
        ];
    }
}
