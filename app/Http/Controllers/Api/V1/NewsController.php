<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\News\StoreNewsRequest;
use App\Http\Requests\News\UpdateNewsRequest;
use App\Http\Resources\Api\V1\NewsResource;
use App\Models\News;
use App\Services\News\NewsService;
use Illuminate\Http\Request;

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

    public function store(StoreNewsRequest $request)
    {
        if (!$request->user()->role->isAdminLevel()) {
            return ApiResponse::error(
                'You do not have permission to create news.',
                403
            );
        }

        $newsItem = $this->service->create($request->validated(), $request->file('image'));

        return ApiResponse::success(
            new NewsResource($newsItem),
            'News created successfully.',
            201
        );
    }

    public function show(News $news)
    {
        return ApiResponse::success(
            new NewsResource($news),
            'News retrieved successfully.'
        );
    }

    public function update(UpdateNewsRequest $request, News $news)
    {
        if (!$request->user()->role->isAdminLevel()) {
            return ApiResponse::error(
                'You do not have permission to update news.',
                403
            );
        }

        $updated = $this->service->update($news, $request->validated(), $request->file('image'));

        return ApiResponse::success(
            new NewsResource($updated),
            'News updated successfully.'
        );
    }

    public function destroy(Request $request, News $news)
    {
        if (!$request->user()->role->isAdminLevel()) {
            return ApiResponse::error(
                'You do not have permission to delete news.',
                403
            );
        }

        $this->service->delete($news);

        return ApiResponse::success(null, 'News deleted successfully.');
    }
}

