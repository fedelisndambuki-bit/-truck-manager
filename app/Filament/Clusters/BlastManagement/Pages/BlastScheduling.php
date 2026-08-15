<?php

namespace App\Filament\Clusters\BlastManagement\Pages;

use App\Filament\Clusters\BlastManagement;
use App\Filament\Clusters\BlastManagement\Resources\BlastPlanResource;
use App\Models\BlastPlan;
use App\Models\Staff;
use App\Traits\HasModuleAccess;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

/**
 * Blast Scheduling board — a computed view over BlastPlan's own scheduling
 * fields (scheduled_date/time, blast_team, safety_officer, security/
 * emergency team), not a duplicate CRUD table.
 */
class BlastScheduling extends Page implements HasTable
{
    use HasModuleAccess;
    use InteractsWithTable;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'blast.view';
    }

    protected static ?string $cluster = BlastManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Blast Scheduling';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Blast Scheduling';

    protected static string $view = 'filament.clusters.blast-management.pages.blast-scheduling';

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />',
            'title' => 'Blast Scheduling',
            'subtitle' => 'Upcoming scheduled blasts, team assignments and equipment requirements.',
        ]);
    }

    public function table(Table $table): Table
    {
        $companyId = Auth::user()?->company_id;

        return $table
            ->query(
                BlastPlan::query()
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->whereNotNull('scheduled_date')
                    ->with(['extractionSite', 'safetyOfficer'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('blast_number')->label('Blast #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('blast_name'),
                Tables\Columns\TextColumn::make('extractionSite.name')->label('Quarry'),
                Tables\Columns\TextColumn::make('scheduled_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('scheduled_time')->time('H:i')->placeholder('—'),
                Tables\Columns\TextColumn::make('safetyOfficer.full_name')->label('Safety Officer')->placeholder('—'),
                Tables\Columns\TextColumn::make('blast_team')
                    ->label('Blast Team')
                    ->state(fn (BlastPlan $record): string => empty($record->blast_team)
                        ? '—'
                        : Staff::query()->whereIn('id', $record->blast_team)->pluck('full_name')->implode(', ')),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => BlastPlanResource::getStatusColor($state)),
            ])
            ->defaultSort('scheduled_date', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(BlastPlanResource::getStatusOptions()),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (BlastPlan $record) => BlastPlanResource::getUrl('view', ['record' => $record])),
            ])
            ->striped();
    }
}
