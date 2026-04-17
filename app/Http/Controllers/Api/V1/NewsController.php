<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\NewsResource;
use App\Models\News;
use App\Services\News\NewsService;

class NewsController extends Controller
{
    public function __construct(
        private readonly NewsService $service
    ) {}

    public function index()
    {
        $news = $this->service->listPublished();

        return ApiResponse::success(
            NewsResource::collection($news),
            'News retrieved successfully.'
        );
    }

    public function show(News $news)
    {
        return ApiResponse::success(
            new NewsResource($this->service->getById($news)),
            'News retrieved successfully.'
        );
    }
}
