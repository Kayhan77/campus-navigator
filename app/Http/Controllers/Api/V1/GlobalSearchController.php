<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Search\GlobalSearchRequest;
use App\Http\Resources\Api\V1\BuildingResource;
use App\Http\Resources\Api\V1\EventResource;
use App\Http\Resources\Api\V1\LostItemResource;
use App\Http\Resources\Api\V1\RoomResource;
use App\Services\Search\GlobalSearchService;

/**
 * GET /api/v1/search?q=<term>&per_model=5
 *
 * Searches Events, Buildings, Rooms, and LostItems in parallel using DB
 * LIKE queries. Enforces a minimum search term length to prevent runaway
 * full-table scans.
 *
 * Query parameters:
 *  - q          (required) Search term; min length defined in config('search.min_search_length')
 *  - per_model  (optional) Max results per model group (default 5, max 20)
 */
class GlobalSearchController extends Controller
{
    public function __construct(private readonly GlobalSearchService $searchService)
    {
    }

    public function __invoke(GlobalSearchRequest $request)
    {
        $result = $this->searchService->search(
            term: $request->term(),
            perModel: $request->perModel(),
        );

        $data = [
            'query'      => $result['query'],
            'events'     => EventResource::collection($result['events']),
            'buildings'  => BuildingResource::collection($result['buildings']),
            'rooms'      => RoomResource::collection($result['rooms']),
            'lost_items' => LostItemResource::collection($result['lost_items']),
            'counts'     => [
                'events'     => $result['events']->count(),
                'buildings'  => $result['buildings']->count(),
                'rooms'      => $result['rooms']->count(),
                'lost_items' => $result['lost_items']->count(),
            ],
        ];

        return ApiResponse::success($data, "Search results for \"{$result['query']}\".");
    }
}
