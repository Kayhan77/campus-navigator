<?php

namespace App\Services\Cache;

/**
 * Central registry of Redis cache tag namespaces.
 *
 * Every domain that uses SearchCacheService must reference its tag from
 * here. Observers (write path / invalidation) and controllers/services
 * (read path / remember) both import this class, guaranteeing the tag
 * string is defined in exactly one place and cannot silently diverge.
 *
 * Usage:
 *   // Reading (controller)
 *   $cache->remember(CacheTags::EVENTS, $key, fn () => ...);
 *
 *   // Invalidating (observer)
 *   $this->cache->invalidate(CacheTags::EVENTS);
 */
final class CacheTags
{
    // -------------------------------------------------------------------------
    // Core domain models
    // -------------------------------------------------------------------------

    /** Paginated listings, filtered searches, building detail views */
    public const BUILDINGS = 'buildings';

    /** Paginated listings, filtered searches, calendar, upcoming queries */
    public const EVENTS = 'events';

    /** Paginated listings, filtered searches, room detail views */
    public const ROOMS = 'rooms';

    /** Paginated listings, filtered searches, lost-item reports */
    public const LOST_ITEMS = 'lost_items';

    /** Paginated listings, filtered searches, schedule detail views */
    public const ACADEMIC_SCHEDULES = 'academic_schedules';

    // -------------------------------------------------------------------------
    // Admin / user-scoped
    // -------------------------------------------------------------------------

    /** Admin user list, role pages */
    public const USERS = 'users';

    /** In-app notification listings */
    public const NOTIFICATIONS = 'notifications';

    // -------------------------------------------------------------------------
    // Cross-model aggregates
    // -------------------------------------------------------------------------

    /**
     * Dashboard stats (counts, latest records).
     * Invalidated when any of its source models change, or on a short TTL.
     */
    public const DASHBOARD = 'dashboard';

    // Prevent instantiation — this class is a pure constants namespace.
    private function __construct() {}
}
