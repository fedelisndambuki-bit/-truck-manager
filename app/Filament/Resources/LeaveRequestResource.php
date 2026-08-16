<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveRequestResource\Pages;
use App\Models\LeaveRequest;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class LeaveRequestResource extends Resource
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

    protected static ?string $model = LeaveRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Leave Management';

    protected static ?string $navigationGroup = 'Labour & HR Management';

    protected static ?int $navigationSort = 8;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Leave Request')->schema([
                    Forms\Components\Select::make('staff_id')
                        ->label('Employee')
                        ->relationship('staff', 'full_name')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('leave_type_id')
                        ->label('Leave Type')
                        ->relationship('leaveType', 'name')
                        ->searchable()->preload()->required()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')->required(),
                            Forms\Components\TextInput::make('default_days_per_year')->numeric()->default(21),
                        ]),
                    Forms\Components\DatePicker::make('start_date')->required()->reactive(),
                    Forms\Components\DatePicker::make('end_date')->required()->reactive(),
                    Forms\Components\Select::make('status')
                        ->options(['Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected'])
                        ->default('Pending')
                        ->required(),
                    Forms\Components\Textarea::make('reason')->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('staff.full_name')->label('Employee')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('leaveType.name')->label('Type'),
                Tables\Columns\TextColumn::make('start_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('end_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('days')->label('Days'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'Approved' => 'success',
                    'Rejected' => 'danger',
                    default => 'warning',
                }),
                Tables\Columns\TextColumn::make('approvedBy.name')->label('Approved By')->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected',
                ]),
                Tables\Filters\SelectFilter::make('leave_type_id')->label('Leave Type')->relationship('leaveType', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (LeaveRequest $record) {
                        $record->update(['status' => 'Approved', 'approved_by_id' => Auth::id(), 'approved_at' => now()]);
                        Notification::make()->title('Leave approved')->success()->send();
                    })
                    ->visible(fn (LeaveRequest $record) => $record->status === 'Pending'
                        && (Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('hr.leave_approval'))),
                Tables\Actions\Action::make('reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (LeaveRequest $record) {
                        $record->update(['status' => 'Rejected', 'approved_by_id' => Auth::id(), 'approved_at' => now()]);
                        Notification::make()->title('Leave rejected')->danger()->send();
                    })
                    ->visible(fn (LeaveRequest $record) => $record->status === 'Pending'
                        && (Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('hr.leave_approval'))),
                Tables\Actions\Action::make('submitForApproval')
                    ->label('Submit for Approval')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (LeaveRequest $record) => $record->status === 'Pending' && ! $record->isApprovalPending())
                    ->action(function (LeaveRequest $record) {
                        $record->submitForApproval('leave-request-approval', [
                            'title' => 'Leave Request — ' . ($record->staff?->full_name ?? $record->id),
                            'metadata' => ['staff_id' => $record->staff_id],
                        ]);
                        Notification::make()->title('Submitted to the Workflow & Approvals engine')->success()->send();
                    }),
                Tables\Actions\Action::make('viewApproval')
                    ->label('View Approval')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->visible(fn (LeaveRequest $record) => $record->currentApproval() !== null)
                    ->url(fn (LeaveRequest $record) => \App\Filament\Clusters\WorkflowApprovals\Resources\ApprovalRequestResource\Pages\ViewApprovalRequest::getUrl(['record' => $record->currentApproval()])),
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
        return parent::getEloquentQuery()->with(['staff', 'leaveType', 'approvedBy']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaveRequests::route('/'),
            'create' => Pages\CreateLeaveRequest::route('/create'),
            'edit' => Pages\EditLeaveRequest::route('/{record}/edit'),
        ];
    }
}
