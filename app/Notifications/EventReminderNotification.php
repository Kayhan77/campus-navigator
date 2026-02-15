<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class EventReminderNotification extends Notification
{
    use Queueable;

    protected Event $event;

    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    public function via($notifiable)
    {
        return ['mail']; // can also add database or push
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject('Upcoming Event Reminder')
                    ->line("Event '{$this->event->title}' starts at {$this->event->start_time}")
                    ->action('View Event', url('/events/' . $this->event->id));
    }
}
