<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BuildingResource;
use App\Http\Resources\Api\V1\EventResource;
use App\Http\Resources\Api\V1\LostItemResource;
use App\Http\Resources\Api\V1\RoomResource;
use App\Models\Building;
use App\Models\Event;
use App\Models\LostItem;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
    private const MAX_PER_MODEL = 20;
    private const DEFAULT_PER_MODEL = 5;

    public function __invoke(Request $request)
    {
        $request->validate([
            'q'         => ['required', 'string', 'min:' . config('search.min_search_length', 2), 'max:100'],
            'per_model' => ['sometimes', 'integer', 'min:1', 'max:' . self::MAX_PER_MODEL],
        ]);

        $term      = $request->input('q');
        $perModel  = (int) $request->input('per_model', self::DEFAULT_PER_MODEL);
        $perModel  = min($perModel, self::MAX_PER_MODEL);
        $like      = '%' . addcslashes($term, '%_\\') . '%';

        // Run all four queries — Eloquent is lazy so they are dispatched together
        $events = Event::where(function ($q) use ($like) {
                $q->where('title', 'like', $like)
                  ->orWhere('description', 'like', $like)
                  ->orWhere('location', 'like', $like);
            })
            ->latest()
            ->limit($perModel)
            ->get();

        $buildings = Building::where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                  ->orWhere('description', 'like', $like);
            })
            ->latest()
            ->limit($perModel)
            ->get();

        $rooms = Room::with('building')
            ->where('room_number', 'like', $like)
            ->latest()
            ->limit($perModel)
            ->get();

        $lostItems = LostItem::with('user')
            ->where(function ($q) use ($like) {
                $q->where('title', 'like', $like)
                  ->orWhere('description', 'like', $like)
                  ->orWhere('location', 'like', $like);
            })
            ->latest()
            ->limit($perModel)
            ->get();

        $data = [
            'query'      => $term,
            'events'     => EventResource::collection($events),
            'buildings'  => BuildingResource::collection($buildings),
            'rooms'      => RoomResource::collection($rooms),
            'lost_items' => LostItemResource::collection($lostItems),
            'counts'     => [
                'events'     => $events->count(),
                'buildings'  => $buildings->count(),
                'rooms'      => $rooms->count(),
                'lost_items' => $lostItems->count(),
            ],
        ];

        return ApiResponse::success($data, "Search results for \"{$term}\".");
    }
}
