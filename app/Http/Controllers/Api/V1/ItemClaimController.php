<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\ItemClaim\StoreItemClaimRequest;
use App\Http\Resources\Api\V1\ItemClaimResource;
use App\Models\ItemClaim;
use App\Models\LostItem;
use App\Services\ItemClaimService;

class ItemClaimController extends Controller
{
    public function __construct(
        private readonly ItemClaimService $service
    ) {}

    public function store(StoreItemClaimRequest $request)
    {
        $this->authorize('create', ItemClaim::class);

        $claim = $this->service->createClaim($request->user()->id, $request->validated());

        return ApiResponse::success(
            new ItemClaimResource($claim->load('user')),
            'Claim submitted successfully.',
            201
        );
    }

    public function index(LostItem $lostItem)
    {
        $this->authorize('manageClaims', $lostItem);

        $claims = $this->service->getClaimsForItem($lostItem);

        return ApiResponse::success(
            ItemClaimResource::collection($claims),
            'Claims retrieved successfully.'
        );
    }

    public function accept(ItemClaim $claim)
    {
        $this->authorize('accept', $claim);

        $accepted = $this->service->acceptClaim($claim);

        return ApiResponse::success(
            new ItemClaimResource($accepted),
            'Claim accepted and item marked as found.'
        );
    }

    public function reject(ItemClaim $claim)
    {
        $this->authorize('reject', $claim);

        $rejected = $this->service->rejectClaim($claim);

        return ApiResponse::success(
            new ItemClaimResource($rejected),
            'Claim rejected successfully.'
        );
    }
}
