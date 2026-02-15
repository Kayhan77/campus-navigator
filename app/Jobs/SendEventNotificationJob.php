<?php

namespace App\Jobs;

use App\Models\Event;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use App\Notifications\EventReminderNotification;

class SendEventNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $events = Event::whereBetween('start_time', [now(), now()->addDay()])->get();

        foreach ($events as $event) {
            $users = User::all(); // or filter by role
            Notification::send($users, new EventReminderNotification($event));
        }
    }
}
