<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Resources\Api\V1\NotificationResource;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user with pagination.
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $isRead = $request->query('is_read');

        $query = Notification::query()
            ->whereHas('recipients', function ($q) {
                $q->where('user_id', Auth::id());
            })
            ->with(['recipients' => function ($q) {
                $q->where('user_id', Auth::id());
            }])
            ->orderByDesc('created_at');

        // Filter by read status if provided
        if ($isRead !== null) {
            $query->whereHas('recipients', function ($q) use ($isRead) {
                $q->where('user_id', Auth::id())
                    ->where('is_read', (bool) $isRead);
            }, '=', 1);
        }

        $notifications = $query->paginate($perPage);

        return ApiResponse::success(
            NotificationResource::collection($notifications)
                ->response()
                ->getData(true)
        );
    }

    /**
     * Get a specific notification.
     */
    public function show(string $id)
    {
        $notification = Notification::query()
            ->whereHas('recipients', function ($q) use ($id) {
                $q->where('user_id', Auth::id())
                    ->where('notification_id', $id);
            })
            ->with(['recipients' => function ($q) {
                $q->where('user_id', Auth::id());
            }])
            ->findOrFail($id);

        return ApiResponse::success(
            new NotificationResource($notification)
        );
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(string $id)
    {
        $recipient = NotificationRecipient::query()
            ->where('notification_id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $recipient->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return ApiResponse::success(
            [],
            'Notification marked as read.'
        );
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        NotificationRecipient::query()
            ->where('user_id', Auth::id())
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return ApiResponse::success(
            [],
            'All notifications marked as read.'
        );
    }

    /**
     * Get unread notification count.
     */
    public function unreadCount()
    {
        $count = NotificationRecipient::where('user_id', Auth::id())
            ->where('is_read', false)
            ->count();

        return ApiResponse::success([
            'unread_count' => $count,
        ]);
    }
}
