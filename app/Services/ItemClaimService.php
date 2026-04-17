<?php

namespace App\Services;

use App\Models\ItemClaim;
use App\Models\LostItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ItemClaimService
{
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

        return ItemClaim::create([
            'lost_item_id' => $item->id,
            'user_id' => $userId,
            'message' => $data['message'] ?? null,
            'location_found' => $data['location_found'] ?? null,
            'status' => 'pending',
        ]);
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

            return $claim->fresh(['user', 'lostItem']);
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

        return $claim->fresh(['user', 'lostItem']);
    }
}
