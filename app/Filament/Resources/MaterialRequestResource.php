<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaterialRequestResource\Pages;
use App\Models\MaterialRequest;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class MaterialRequestResource extends Resource
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

    protected static ?string $model = MaterialRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Material Requests';

    protected static ?string $navigationGroup = 'Materials Management';

    protected static ?int $navigationSort = 6;

    public static function getPriorityOptions(): array
    {
        return ['Low' => 'Low', 'Medium' => 'Medium', 'High' => 'High', 'Urgent' => 'Urgent'];
    }

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            'Approved' => 'success',
            'Rejected' => 'danger',
            'Partially Approved' => 'warning',
            default => 'gray',
        };
    }

    public static function projectSiteSchema(): array
    {
        return [
            Forms\Components\Select::make('project_id')
                ->label('Project')
                ->relationship('project', 'name')
                ->searchable()->preload(),
            Forms\Components\Select::make('site_id')
                ->label('Site')
                ->relationship('site', 'name')
                ->searchable()->preload(),
            Forms\Components\Select::make('requested_by_id')
                ->label('Requested By')
                ->relationship('requestedBy', 'full_name')
                ->searchable()->preload(),
            Forms\Components\DatePicker::make('required_date')->default(now()->addDays(7)),
            Forms\Components\Select::make('priority')->options(self::getPriorityOptions())->default('Medium')->required(),
        ];
    }

    public static function itemsSchema(): array
    {
        return [
            Forms\Components\Repeater::make('items')
                ->relationship('items')
                ->schema([
                    Forms\Components\Select::make('material_id')
                        ->label('Material')
                        ->relationship('material', 'name')
                        ->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('requested_quantity')->numeric()->required(),
                    Forms\Components\TextInput::make('approved_quantity')->numeric(),
                ])
                ->columns(3)
                ->columnSpanFull()
                ->defaultItems(1)
                ->addActionLabel('Add Material'),
        ];
    }

    public static function purposeSchema(): array
    {
        return [
            Forms\Components\Textarea::make('purpose')->columnSpanFull(),
            Forms\Components\Textarea::make('notes')->columnSpanFull(),
        ];
    }

    public static function approvalSchema(): array
    {
        return [
            Forms\Components\Select::make('status')->options([
                'Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected', 'Partially Approved' => 'Partially Approved',
            ])->default('Pending')->required(),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Request Details')->schema(self::projectSiteSchema())->columns(3),
                Forms\Components\Section::make('Material List')->schema(self::itemsSchema()),
                Forms\Components\Section::make('Purpose & Notes')->schema(self::purposeSchema()),
                Forms\Components\Section::make('Approval')->schema(self::approvalSchema()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('request_number')->label('Request #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('project.name')->label('Project')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('site.name')->label('Site')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('requestedBy.full_name')->label('Requested By')->placeholder('—'),
                Tables\Columns\TextColumn::make('priority')->badge()->color(fn (string $state) => match ($state) {
                    'Urgent' => 'danger', 'High' => 'warning', 'Medium' => 'info', default => 'gray',
                }),
                Tables\Columns\TextColumn::make('required_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('items_count')->counts('items')->label('Line Items'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected', 'Partially Approved' => 'Partially Approved',
                ]),
                Tables\Filters\SelectFilter::make('priority')->options(self::getPriorityOptions()),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (MaterialRequest $record) {
                        $record->update(['status' => 'Approved']);
                        $record->items()->update(['approved_quantity' => \Illuminate\Support\Facades\DB::raw('requested_quantity')]);
                        Notification::make()->title('Material request approved')->success()->send();
                    })
                    ->visible(fn (MaterialRequest $record) => $record->status === 'Pending'
                        && (Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('materials.approve'))),
                Tables\Actions\Action::make('reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (MaterialRequest $record) {
                        $record->update(['status' => 'Rejected']);
                        Notification::make()->title('Material request rejected')->danger()->send();
                    })
                    ->visible(fn (MaterialRequest $record) => $record->status === 'Pending'
                        && (Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('materials.approve'))),
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
            'index' => Pages\ListMaterialRequests::route('/'),
            'create' => Pages\CreateMaterialRequest::route('/create'),
            'edit' => Pages\EditMaterialRequest::route('/{record}/edit'),
        ];
    }
}
