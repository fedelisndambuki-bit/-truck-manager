<?php

namespace App\Filament\Clusters\Procurement\Resources\SupplierResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PerformanceReviewsRelationManager extends RelationManager
{
    protected static string $relationship = 'performanceReviews';

    protected static ?string $title = 'Performance Reviews';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('review_period_start')->required(),
            Forms\Components\DatePicker::make('review_period_end')->required(),
            Forms\Components\TextInput::make('on_time_delivery_score')->numeric()->minValue(0)->maxValue(100)->suffix('/ 100'),
            Forms\Components\TextInput::make('quality_score')->numeric()->minValue(0)->maxValue(100)->suffix('/ 100'),
            Forms\Components\TextInput::make('pricing_score')->numeric()->minValue(0)->maxValue(100)->suffix('/ 100'),
            Forms\Components\TextInput::make('responsiveness_score')->numeric()->minValue(0)->maxValue(100)->suffix('/ 100'),
            Forms\Components\TextInput::make('compliance_score')->numeric()->minValue(0)->maxValue(100)->suffix('/ 100'),
            Forms\Components\Textarea::make('comments')->columnSpanFull(),
        ])->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('review_period_start')
            ->columns([
                Tables\Columns\TextColumn::make('review_period_start')->date()->label('From'),
                Tables\Columns\TextColumn::make('review_period_end')->date()->label('To'),
                Tables\Columns\TextColumn::make('overall_score')->label('Overall')->suffix(' / 100'),
                Tables\Columns\TextColumn::make('on_time_delivery_score')->suffix(' / 100')->placeholder('—'),
                Tables\Columns\TextColumn::make('quality_score')->suffix(' / 100')->placeholder('—'),
            ])
            ->defaultSort('review_period_end', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make()->mutateFormDataUsing(function (array $data) {
                    $data['reviewed_by_id'] = Auth::id();

                    return $data;
                }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
