<?php

namespace App\Services\Admin;

use App\Models\Announcement;
use App\Models\Building;
use App\Models\Event;
use App\Models\LostItem;
use App\Models\News;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminDashboardService
{
    private const DASHBOARD_CACHE_KEY = 'admin_dashboard_stats';
    private const TIMEZONE_OFFSET = '+03:00';

    /**
     * Aggregate stats for the admin dashboard.
     */
    public function getStats(): array
    {
        $cacheTtl = (int) config('cache.admin_dashboard_ttl', 60);

        return Cache::remember(self::DASHBOARD_CACHE_KEY, now()->addSeconds($cacheTtl), function () {
            return [
                'total_users' => User::count(),
                'total_events' => Event::count(),
                'total_buildings' => Building::count(),
                'total_news' => News::count(),
                'total_announcements' => Announcement::count(),
                'total_lost_items' => LostItem::count(),
                'total_found_items' => LostItem::where('status', 'found')->count(),
                'latest_users' => User::latest()->limit(5)->get(),
                'latest_events' => Event::latest()->limit(5)->get(),
                'users_per_day' => $this->usersPerDay(),
                'users_per_month' => $this->usersPerMonth(),
                'events_per_day' => $this->eventsPerDay(),
                'events_per_month' => $this->eventsPerMonth(),
                'lost_items_per_day' => $this->lostItemsPerDay(),
                'lost_items_per_month' => $this->lostItemsPerMonth(),
            ];
        });
    }

    public function usersPerDay(): Collection
    {
        return $this->aggregatePerDay(User::class);
    }

    public function usersPerMonth(): Collection
    {
        return $this->aggregatePerMonth(User::class);
    }

    public function eventsPerDay(): Collection
    {
        return $this->aggregatePerDay(Event::class);
    }

    public function eventsPerMonth(): Collection
    {
        return $this->aggregatePerMonth(Event::class);
    }

    public function lostItemsPerDay(): Collection
    {
        return $this->aggregatePerDay(LostItem::class);
    }

    public function lostItemsPerMonth(): Collection
    {
        return $this->aggregatePerMonth(LostItem::class);
    }

    private function aggregatePerDay(string $modelClass, int $days = 30): Collection
    {
        $dateExpression = $this->dateExpression();

        return $modelClass::query()
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw("{$dateExpression} as date, COUNT(*) as total")
            ->groupByRaw($dateExpression)
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'total' => (int) $row->total,
            ]);
    }

    private function aggregatePerMonth(string $modelClass, int $months = 30): Collection
    {
        $monthExpression = $this->monthExpression();

        return $modelClass::query()
            ->where('created_at', '>=', now()->subMonths($months))
            ->selectRaw("{$monthExpression} as month, COUNT(*) as total")
            ->groupByRaw($monthExpression)
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => [
                'month' => $row->month,
                'total' => (int) $row->total,
            ]);
    }

    private function dateExpression(): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'pgsql' => "DATE(created_at AT TIME ZONE '" . self::TIMEZONE_OFFSET . "')",
            'sqlite' => 'DATE(created_at)',
            default => "DATE(CONVERT_TZ(created_at, '+00:00', '" . self::TIMEZONE_OFFSET . "'))",
        };
    }

    private function monthExpression(): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'pgsql' => "TO_CHAR(created_at AT TIME ZONE '" . self::TIMEZONE_OFFSET . "', 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', created_at)",
            default => "DATE_FORMAT(CONVERT_TZ(created_at, '+00:00', '" . self::TIMEZONE_OFFSET . "'), '%Y-%m')",
        };
    }
}
