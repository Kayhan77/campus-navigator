<?php

namespace App\Traits;

use App\Filters\QueryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Adds a `filter()` and `withAllowed()` Eloquent scope to any model.
 *
 * Usage:
 *
 *   Event::filter($filter)->withAllowed($request, ['room'])->paginate(15);
 */
trait Filterable
{
    /**
     * Apply the given QueryFilter to the Eloquent builder.
     */
    public function scopeFilter(Builder $query, QueryFilter $filter): Builder
    {
        return $filter->apply($query);
    }

    /**
     * Eager-load relations safely using a whitelist.
     *
     * - $defaults  : relations the controller always loads (developer-controlled,
     *                no user input involved — fully trusted).
     * - ?include=  : optional comma-separated client request; ONLY names that
     *                appear in the model's $allowedIncludes are honoured.
     *                Anything else is silently dropped — no exception, no leak.
     *
     * This prevents RelationNotFoundException caused by arbitrary query params
     * (e.g. ?per_page=1000, ?include=password) being passed to ->with().
     *
     * @param  Builder   $query
     * @param  Request   $request
     * @param  string[]  $defaults  Relations always loaded regardless of client input
     * @return Builder
     */
    public function scopeWithAllowed(Builder $query, Request $request, array $defaults = []): Builder
    {
        $model   = $query->getModel();

        // Each model declares which relations the client may request.
        // If the property is absent, no client-driven loading is permitted.
        $whitelist = property_exists($model, 'allowedIncludes')
            ? (array) $model->allowedIncludes
            : [];

        // Parse ?include=room,building  — comma-separated string
        $rawInput  = (string) $request->input('include', '');
        $requested = array_filter(array_map('trim', explode(',', $rawInput)));

        // Intersect with whitelist — unknown names are silently dropped
        $safeExtras = array_values(array_intersect($requested, $whitelist));

        // Merge developer defaults (always trusted) + safe client extras
        $toLoad = array_values(array_unique(array_merge($defaults, $safeExtras)));

        return empty($toLoad) ? $query : $query->with($toLoad);
    }
}
