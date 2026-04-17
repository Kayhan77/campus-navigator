<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\News\CreateNewsDTO;
use App\DTOs\News\UpdateNewsDTO;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\News\StoreNewsRequest;
use App\Http\Requests\News\UpdateNewsRequest;
use App\Http\Resources\Api\V1\NewsResource;
use App\Models\News;
use App\Services\News\NewsService;

class AdminNewsController extends Controller
{
    public function __construct(
        private readonly NewsService $service
    ) {}

    public function index()
    {
        $news = $this->service->listAdminPaginated()
            ->through(fn ($item) => new NewsResource($item));

        return ApiResponse::paginated($news, 'News retrieved successfully.');
    }

    public function show(News $news)
    {
        return ApiResponse::success(
            new NewsResource($this->service->getById($news)),
            'News retrieved successfully.'
        );
    }

    public function store(StoreNewsRequest $request)
    {
        $this->authorize('create', News::class);
        $dto  = CreateNewsDTO::fromRequest($request);
        $news = $this->service->create($dto, $request->file('image'));

        return ApiResponse::success(new NewsResource($news), 'News created successfully.', 201);
    }

    public function update(UpdateNewsRequest $request, News $news)
    {
        $this->authorize('update', $news);
        $dto     = UpdateNewsDTO::fromRequest($request);
        $updated = $this->service->update($news, $dto, $request->file('image'));

        return ApiResponse::success(new NewsResource($updated), 'News updated successfully.');
    }

    public function destroy(News $news)
    {
        $this->authorize('delete', $news);
        $this->service->delete($news);

        return ApiResponse::success(null, 'News deleted successfully.');
    }
}
