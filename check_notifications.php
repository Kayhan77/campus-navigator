<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check if there are any device tokens
$deviceTokens = \App\Models\DeviceToken::all(['user_id', 'platform', 'token']);
echo "Total device tokens: " . $deviceTokens->count() . PHP_EOL;
foreach ($deviceTokens as $token) {
    $masked = substr($token->token, 0, 8) . '...' . substr($token->token, -4);
    echo "- User: {$token->user_id}, Platform: {$token->platform}, Token: $masked" . PHP_EOL;
}

// Check recent notifications
$notifications = \App\Models\Notification::latest()->take(5)->get(['id', 'title', 'type', 'sender_id', 'created_at']);
echo "\n\nRecent notifications:" . PHP_EOL;
foreach ($notifications as $notif) {
    echo "- ID: {$notif->id}, Type: {$notif->type}, Title: {$notif->title}, SenderID: {$notif->sender_id}, Created: {$notif->created_at}" . PHP_EOL;
}

// Check notification recipients for the most recent event notification
$eventNotif = \App\Models\Notification::where('type', 'event')->latest()->first();
if ($eventNotif) {
    echo "\n\nEvent notification (ID {$eventNotif->id}) - Created at {$eventNotif->created_at}:" . PHP_EOL;
    $recipients = \App\Models\NotificationRecipient::where('notification_id', $eventNotif->id)->get(['user_id', 'read_at', 'delivered_at']);
    echo "Recipients: " . $recipients->count() . PHP_EOL;
    foreach ($recipients as $recipient) {
        $delivered = $recipient->delivered_at ? 'YES' : 'NO';
        $read = $recipient->read_at ? 'YES' : 'NO';
        echo "- User: {$recipient->user_id}, Delivered: $delivered, Read: $read" . PHP_EOL;
    }
}
