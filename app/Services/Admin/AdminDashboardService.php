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

class AdminDashboardService
{
    private const DASHBOARD_CACHE_KEY = 'admin_dashboard_stats';

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
        return $modelClass::query()
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw("DATE(CONVERT_TZ(created_at, '+00:00', '+03:00')) as date, COUNT(*) as total")
            ->groupByRaw("DATE(CONVERT_TZ(created_at, '+00:00', '+03:00'))")
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'total' => (int) $row->total,
            ]);
    }

    private function aggregatePerMonth(string $modelClass, int $months = 30): Collection
    {
        return $modelClass::query()
            ->where('created_at', '>=', now()->subMonths($months))
            ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as total')
            ->groupByRaw('YEAR(created_at), MONTH(created_at)')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => [
                'month' => sprintf('%04d-%02d', (int) $row->year, (int) $row->month),
                'total' => (int) $row->total,
            ]);
    }
}
