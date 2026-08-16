<?php

namespace App\Filament\Clusters\DocumentManagement\Resources;

use App\Filament\Clusters\DocumentManagement;
use App\Filament\Clusters\DocumentManagement\Resources\ShopDrawingResource\Pages;
use App\Models\Drawing;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shop drawings — the same `drawings` table as DrawingResource, scoped to
 * `type = 'Shop Drawing'`, with the extra consultant/client double sign-off.
 */
class ShopDrawingResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'documents.view';
    }

    protected static ?string $model = Drawing::class;

    protected static ?string $cluster = DocumentManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Shop Drawings';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', 'Shop Drawing')->with(['project']);
    }

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            'Approved' => 'success',
            'Submitted', 'Under Review' => 'warning',
            'Rejected', 'Revision Required' => 'danger',
            'Archived' => 'gray',
            default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('type')->default('Shop Drawing'),
                Forms\Components\Section::make('Shop Drawing Details')->schema([
                    Forms\Components\TextInput::make('title')->required()->columnSpanFull(),
                    Forms\Components\Select::make('discipline')->options([
                        'Architectural' => 'Architectural', 'Structural' => 'Structural',
                        'MEP' => 'MEP', 'Civil' => 'Civil',
                    ])->required(),
                    Forms\Components\Select::make('project_id')
                        ->relationship('project', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('status')->options([
                        'Draft' => 'Draft', 'Submitted' => 'Submitted', 'Under Review' => 'Under Review',
                        'Approved' => 'Approved', 'Rejected' => 'Rejected', 'Revision Required' => 'Revision Required',
                        'Archived' => 'Archived',
                    ])->default('Draft')->required(),
                    Forms\Components\Textarea::make('description')->columnSpanFull(),
                ])->columns(3),
                Forms\Components\Section::make('File')->schema([
                    Forms\Components\FileUpload::make('file_path')
                        ->disk('public')->directory('edms/shop-drawings')
                        ->openable()->downloadable()->columnSpanFull(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('drawing_number')->label('Shop Drawing #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('title')->searchable()->limit(35),
                Tables\Columns\TextColumn::make('project.name')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('current_version')->label('Version'),
                Tables\Columns\IconColumn::make('consultant_approved_at')->label('Consultant')->boolean()
                    ->getStateUsing(fn (Drawing $record) => (bool) $record->consultant_approved_at),
                Tables\Columns\IconColumn::make('client_approved_at')->label('Client')->boolean()
                    ->getStateUsing(fn (Drawing $record) => (bool) $record->client_approved_at),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => static::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')->label('Project')->relationship('project', 'name'),
                Tables\Filters\SelectFilter::make('status')->options([
                    'Draft' => 'Draft', 'Submitted' => 'Submitted', 'Under Review' => 'Under Review',
                    'Approved' => 'Approved', 'Rejected' => 'Rejected', 'Revision Required' => 'Revision Required',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('consultantApprove')
                    ->label('Consultant Approve')
                    ->icon('heroicon-o-check-badge')
                    ->color('info')
                    ->visible(fn (Drawing $record) => ! $record->consultant_approved_at)
                    ->requiresConfirmation()
                    ->action(fn (Drawing $record) => $record->update(['consultant_approved_at' => now()])),
                Tables\Actions\Action::make('clientApprove')
                    ->label('Client Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Drawing $record) => $record->consultant_approved_at && ! $record->client_approved_at)
                    ->requiresConfirmation()
                    ->action(fn (Drawing $record) => $record->update(['client_approved_at' => now(), 'status' => 'Approved'])),
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
            'index' => Pages\ListShopDrawings::route('/'),
            'create' => Pages\CreateShopDrawing::route('/create'),
            'edit' => Pages\EditShopDrawing::route('/{record}/edit'),
        ];
    }
}
