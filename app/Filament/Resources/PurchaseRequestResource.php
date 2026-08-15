<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseRequestResource\Pages;
use App\Models\PurchaseRequest;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PurchaseRequestResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'materials.view';
    }

    protected static ?string $model = PurchaseRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-plus';

    protected static ?string $navigationLabel = 'Purchase Requests';

    protected static ?string $navigationGroup = 'Materials Management';

    protected static ?int $navigationSort = 7;

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            'Approved' => 'success',
            'Rejected' => 'danger',
            'Converted to PO' => 'info',
            default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Purchase Request')->schema([
                    Forms\Components\Select::make('material_id')
                        ->label('Material')
                        ->relationship('material', 'name')
                        ->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('quantity')->numeric()->required(),
                    Forms\Components\Select::make('requester_id')
                        ->label('Requester')
                        ->relationship('requester', 'full_name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('approver_id')
                        ->label('Approver')
                        ->relationship('approver', 'full_name')
                        ->searchable()->preload(),
                    Forms\Components\TextInput::make('estimated_cost')->numeric()->prefix(session('currency', 'KES')),
                    Forms\Components\Select::make('priority')->options([
                        'Low' => 'Low', 'Medium' => 'Medium', 'High' => 'High', 'Urgent' => 'Urgent',
                    ])->default('Medium')->required(),
                    Forms\Components\DatePicker::make('required_delivery_date'),
                    Forms\Components\Select::make('status')->options([
                        'Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected', 'Converted to PO' => 'Converted to PO',
                    ])->default('Pending')->required(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pr_number')->label('PR #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('material.name')->label('Material')->searchable(),
                Tables\Columns\TextColumn::make('quantity')->numeric(2),
                Tables\Columns\TextColumn::make('estimated_cost')->money(session('currency', 'KES'))->placeholder('—'),
                Tables\Columns\TextColumn::make('priority')->badge()->color(fn (string $state) => match ($state) {
                    'Urgent' => 'danger', 'High' => 'warning', default => 'gray',
                }),
                Tables\Columns\TextColumn::make('source')->badge()->color(fn (string $state) => $state === 'Auto' ? 'info' : 'gray'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected', 'Converted to PO' => 'Converted to PO',
                ]),
                Tables\Filters\SelectFilter::make('source')->options(['Auto' => 'Auto', 'Manual' => 'Manual']),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (PurchaseRequest $record) => $record->update(['status' => 'Approved']))
                    ->visible(fn (PurchaseRequest $record) => $record->status === 'Pending'
                        && (Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('materials.approve'))),
                Tables\Actions\Action::make('submitForApproval')
                    ->label('Submit for Approval')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (PurchaseRequest $record) => $record->status === 'Pending' && ! $record->isApprovalPending())
                    ->action(function (PurchaseRequest $record) {
                        $record->submitForApproval('purchase-request-approval', [
                            'title' => 'Purchase Request — ' . $record->pr_number,
                            'metadata' => ['amount' => (float) $record->estimated_cost, 'staff_id' => $record->requester_id],
                        ]);
                        Notification::make()->title('Submitted to the Workflow & Approvals engine')->success()->send();
                    }),
                Tables\Actions\Action::make('viewApproval')
                    ->label('View Approval')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->visible(fn (PurchaseRequest $record) => $record->currentApproval() !== null)
                    ->url(fn (PurchaseRequest $record) => \App\Filament\Clusters\WorkflowApprovals\Resources\ApprovalRequestResource\Pages\ViewApprovalRequest::getUrl(['record' => $record->currentApproval()])),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseRequests::route('/'),
            'create' => Pages\CreatePurchaseRequest::route('/create'),
            'edit' => Pages\EditPurchaseRequest::route('/{record}/edit'),
        ];
    }
}
