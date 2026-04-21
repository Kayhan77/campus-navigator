<?php

namespace App\Services;

use App\Models\ItemClaim;
use App\Models\LostItem;
use App\Services\Notification\NotificationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ItemClaimService
{
    public function __construct(
        private readonly NotificationService $notificationService
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

        if ($item->user) {
            $this->notificationService->sendAndStoreNotification(
                title: 'New Item Claim',
                message: $item->title,
                type: 'system',
                data: ['context' => 'lost_found', 'lost_item_id' => (int) $item->id],
                userIds: [(int) $item->user_id],
                senderId: $userId
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

            if ($accepted->user) {
                $this->notificationService->sendAndStoreNotification(
                    title: 'Claim Accepted',
                    message: $item->title,
                    type: 'system',
                    data: ['context' => 'lost_found', 'lost_item_id' => (int) $item->id],
                    userIds: [(int) $accepted->user_id],
                    senderId: $item->user_id
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
        if ($rejected->user) {
            $this->notificationService->sendAndStoreNotification(
                title: 'Claim Rejected',
                message: $rejected->lostItem?->title ?? 'Lost Item',
                type: 'system',
                data: ['context' => 'lost_found', 'lost_item_id' => (int) $rejected->lost_item_id],
                userIds: [(int) $rejected->user_id],
                senderId: $rejected->lostItem?->user_id
            );
        }

        return $rejected;
    }
}
