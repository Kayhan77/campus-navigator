<?php

namespace App\Http\Controllers\Api\V1\Event;

use App\Http\Controllers\Controller;
use App\Http\Requests\Event\EventRecommendationRequest;
use App\Services\Event\EventRecommendationEngineService;
use Illuminate\Http\JsonResponse;

class EventRecommendationController extends Controller
{
    public function __construct(private readonly EventRecommendationEngineService $service)
    {
    }

    public function __invoke(EventRecommendationRequest $request): JsonResponse
    {
        $result = $this->service->recommend(
            query: $request->userQuery(),
            events: $request->events(),
        );

        // Return the strict recommendation contract directly.
        return response()->json($result);
    }
}
