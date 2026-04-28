<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Search\GlobalSearchSuggestionRequest;
use App\Services\Search\GlobalSearchService;

class GlobalSearchSuggestionController extends Controller
{
    public function __construct(private readonly GlobalSearchService $searchService)
    {
    }

    public function __invoke(GlobalSearchSuggestionRequest $request)
    {
        $limit = (int) config('search.suggestion_limit', 5);
        $suggestions = $this->searchService->suggestions($request->term(), $limit)
            ->map(static fn (array $suggestion): array => [
                'type' => $suggestion['type'],
                'id' => $suggestion['id'],
                'label' => $suggestion['label'],
            ])
            ->values();

        return ApiResponse::success([
            'query' => $request->term(),
            'suggestions' => $suggestions,
        ], 'Search suggestions generated successfully.');
    }
}
