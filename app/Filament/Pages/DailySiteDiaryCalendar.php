<?php

namespace App\Filament\Pages;

use App\Models\DailySiteDiary;
use App\Models\Site;
use App\Traits\HasIndustryAccess;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class DailySiteDiaryCalendar extends Page
{
    use HasIndustryAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Calendar View';

    protected static ?string $navigationGroup = 'Construction';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Diary Calendar';

    protected static string $view = 'filament.pages.daily-site-diary-calendar';

    public string $viewMode = 'month';

    public string $cursor;

    public function mount(): void
    {
        $this->cursor = now()->toDateString();
    }

    public function previousPeriod(): void
    {
        $this->cursor = $this->viewMode === 'month'
            ? Carbon::parse($this->cursor)->subMonth()->toDateString()
            : Carbon::parse($this->cursor)->subWeek()->toDateString();
    }

    public function nextPeriod(): void
    {
        $this->cursor = $this->viewMode === 'month'
            ? Carbon::parse($this->cursor)->addMonth()->toDateString()
            : Carbon::parse($this->cursor)->addWeek()->toDateString();
    }

    public function goToday(): void
    {
        $this->cursor = now()->toDateString();
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = $mode;
    }

    public function getRangeStart(): Carbon
    {
        $cursor = Carbon::parse($this->cursor);

        return $this->viewMode === 'month' ? $cursor->copy()->startOfMonth()->startOfWeek() : $cursor->copy()->startOfWeek();
    }

    public function getRangeEnd(): Carbon
    {
        $cursor = Carbon::parse($this->cursor);

        return $this->viewMode === 'month' ? $cursor->copy()->endOfMonth()->endOfWeek() : $cursor->copy()->endOfWeek();
    }

    public function getDays(): array
    {
        $start = $this->getRangeStart();
        $end = $this->getRangeEnd();
        $days = [];

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $days[] = $day->copy();
        }

        return $days;
    }

    public function getDiariesByDate(): array
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $companyId = Auth::user()?->company_id;

        return $cache = DailySiteDiary::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereBetween('date', [$this->getRangeStart()->toDateString(), $this->getRangeEnd()->toDateString()])
            ->with('site')
            ->get()
            ->groupBy(fn (DailySiteDiary $diary) => $diary->date->toDateString())
            ->toArray();
    }

    public function getActiveSiteCount(): int
    {
        $companyId = Auth::user()?->company_id;

        return Site::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Active')
            ->count();
    }

    public function isMissingDiary(string $date): bool
    {
        if (Carbon::parse($date)->isFuture()) {
            return false;
        }

        return $this->getActiveSiteCount() > 0 && empty($this->getDiariesByDate()[$date] ?? []);
    }
}
