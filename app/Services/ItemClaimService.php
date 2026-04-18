<?php

namespace App\Services;

use App\Models\ItemClaim;
use App\Models\LostItem;
use App\Services\FirebaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ItemClaimService
{
    public function __construct(
        private readonly FirebaseService $firebase
    ) {}

    public function createClaim(int $userId, array $data): ItemClaim
    {
        $item = LostItem::findOrFail($data['lost_item_id']);

        if ($item->status === 'found') {
            throw ValidationException::withMessages([
                'lost_item_id' => 'This lost item has already been resolved.',
            ]);
        }

        if ($item->user_id === $userId) {
            throw ValidationException::withMessages([
                'lost_item_id' => 'You cannot claim your own lost item.',
            ]);
        }

        $alreadyClaimed = ItemClaim::query()
            ->where('lost_item_id', $item->id)
            ->where('user_id', $userId)
            ->exists();

        if ($alreadyClaimed) {
            throw ValidationException::withMessages([
                'lost_item_id' => 'You have already submitted a claim for this item.',
            ]);
        }

        $claim = ItemClaim::create([
            'lost_item_id' => $item->id,
            'user_id' => $userId,
            'message' => $data['message'] ?? null,
            'location_found' => $data['location_found'] ?? null,
            'status' => 'pending',
        ]);

        $item->loadMissing('user.deviceTokens');

        if ($item->user) {
            $this->firebase->sendToUser(
                $item->user,
                'New Item Claim',
                $item->title,
                ['type' => 'lost_found', 'id' => (string) $item->id]
            );
        }

        return $claim;
    }

    public function getClaimsForItem(LostItem $item): Collection
    {
        return ItemClaim::query()
            ->with('user')
            ->where('lost_item_id', $item->id)
            ->latest()
            ->get();
    }

    public function acceptClaim(ItemClaim $claim): ItemClaim
    {
        return DB::transaction(function () use ($claim) {
            $claim->loadMissing('lostItem');
            $item = $claim->lostItem;

            if ($item->status === 'found') {
                throw ValidationException::withMessages([
                    'claim' => 'This item is already resolved.',
                ]);
            }

            if ($claim->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => 'Only pending claims can be accepted.',
                ]);
            }

            $claim->update(['status' => 'accepted']);
            $item->update(['status' => 'found']);

            ItemClaim::query()
                ->where('lost_item_id', $item->id)
                ->where('id', '!=', $claim->id)
                ->where('status', 'pending')
                ->update(['status' => 'rejected']);

            $accepted = $claim->fresh(['user', 'lostItem']);

            $accepted->loadMissing('user.deviceTokens');

            if ($accepted->user) {
                $this->firebase->sendToUser(
                    $accepted->user,
                    'Claim Accepted',
                    $item->title,
                    ['type' => 'lost_found', 'id' => (string) $item->id]
                );
            }

            return $accepted;
        });
    }

    public function rejectClaim(ItemClaim $claim): ItemClaim
    {
        if ($claim->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'Only pending claims can be rejected.',
            ]);
        }

        $claim->update(['status' => 'rejected']);

        $rejected = $claim->fresh(['user', 'lostItem']);
        $rejected->loadMissing('user.deviceTokens');

        if ($rejected->user) {
            $this->firebase->sendToUser(
                $rejected->user,
                'Claim Rejected',
                $rejected->lostItem?->title ?? 'Lost Item',
                ['type' => 'lost_found', 'id' => (string) $rejected->lost_item_id]
            );
        }

        return $rejected;
    }
}
