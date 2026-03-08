<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests, DispatchesJobs;

    /**
     * Resolve the per-page value from the request, clamped to configured limits.
     *
     * Prevents paginate(0) and caps runaway per_page values.
     * Centralised here so no controller ever has to inline this logic.
     */
    protected function resolvePerPage(Request $request): int
    {
        return max(
            1,
            min(
                (int) $request->input('per_page', config('search.default_per_page', 15)),
                config('search.max_per_page', 50)
            )
        );
    }
}
