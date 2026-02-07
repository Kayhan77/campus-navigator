<?php

namespace App\Http\Controllers;

use App\DTOs\LostItem\LostItemData;
use App\Services\LostItemService;
use App\Http\Requests\LostItemRequest;
use App\Http\Resources\LostItemResource;
use App\Models\LostItem;

class LostFoundController extends Controller
{
    protected LostItemService $service;

    public function __construct(LostItemService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return LostItemResource::collection(LostItem::all());
    }

    public function store(LostItemRequest $request)
    {
        $data = new LostItemData($request->validated());
        $item = $this->service->create($data, $request->user()->id);
        return new LostItemResource($item);
    }
}
