<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenderInvitationResource\Pages;
use App\Filament\Resources\TenderInvitationResource\RelationManagers\ClarificationsRelationManager;
use App\Filament\Resources\TenderInvitationResource\RelationManagers\InvitedSubcontractorsRelationManager;
use App\Models\TenderInvitation;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TenderInvitationResource extends Resource
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

    protected static ?string $model = TenderInvitation::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Tender Invitations';

    protected static ?string $navigationGroup = 'Subcontractor Management';

    protected static ?int $navigationSort = 4;

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            'Open' => 'info',
            'Closed' => 'warning',
            'Awarded' => 'success',
            'Cancelled' => 'danger',
            default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Tender Details')->schema([
                    Forms\Components\Select::make('project_id')
                        ->label('Project')
                        ->relationship('project', 'name')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('site_id')
                        ->label('Site')
                        ->relationship('site', 'name')
                        ->searchable()->preload(),
                    Forms\Components\DatePicker::make('closing_date'),
                    Forms\Components\TextInput::make('estimated_value')->numeric()->prefix(session('currency', 'KES')),
                    Forms\Components\Select::make('status')->options([
                        'Draft' => 'Draft', 'Open' => 'Open', 'Closed' => 'Closed', 'Awarded' => 'Awarded', 'Cancelled' => 'Cancelled',
                    ])->default('Draft')->required(),
                    Forms\Components\Textarea::make('scope_description')->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tender_number')->label('Tender #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('project.name')->label('Project')->searchable(),
                Tables\Columns\TextColumn::make('site.name')->label('Site')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('closing_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('estimated_value')->money(session('currency', 'KES'))->placeholder('—'),
                Tables\Columns\TextColumn::make('invited_subcontractors_count')->counts('invitedSubcontractors')->label('Invited'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'Draft' => 'Draft', 'Open' => 'Open', 'Closed' => 'Closed', 'Awarded' => 'Awarded', 'Cancelled' => 'Cancelled',
                ]),
            ])
            ->actions([
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

    public static function getRelations(): array
    {
        return [
            InvitedSubcontractorsRelationManager::class,
            ClarificationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenderInvitations::route('/'),
            'create' => Pages\CreateTenderInvitation::route('/create'),
            'edit' => Pages\EditTenderInvitation::route('/{record}/edit'),
        ];
    }
}
