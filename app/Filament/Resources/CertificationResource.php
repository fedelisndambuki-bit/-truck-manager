<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CertificationResource\Pages;
use App\Models\Certification;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CertificationResource extends Resource
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

    protected static ?string $model = Certification::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationLabel = 'Skills & Certifications';

    protected static ?string $navigationGroup = 'Labour & HR Management';

    protected static ?int $navigationSort = 12;

    public static function getCategoryOptions(): array
    {
        return [
            'Trade' => 'Trade',
            'Equipment License' => 'Equipment License',
            'Professional' => 'Professional',
            'Government License' => 'Government License',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Certification')->schema([
                    Forms\Components\Select::make('staff_id')
                        ->label('Employee')
                        ->relationship('staff', 'full_name')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('category')->options(self::getCategoryOptions())->required(),
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\TextInput::make('number'),
                    Forms\Components\TextInput::make('issuing_body'),
                    Forms\Components\DatePicker::make('issue_date'),
                    Forms\Components\DatePicker::make('expiry_date'),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('staff.full_name')->label('Employee')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category')->badge(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('issuing_body')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('expiry_date')->date()->placeholder('—')->sortable()
                    ->color(fn (?string $state) => $state && \Carbon\Carbon::parse($state)->isPast() ? 'danger' : null),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')->options(self::getCategoryOptions()),
                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Expiring in 30 days')
                    ->query(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->whereBetween('expiry_date', [now(), now()->addDays(30)])),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('expiry_date')
            ->striped();
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['staff']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCertifications::route('/'),
            'create' => Pages\CreateCertification::route('/create'),
            'edit' => Pages\EditCertification::route('/{record}/edit'),
        ];
    }
}
