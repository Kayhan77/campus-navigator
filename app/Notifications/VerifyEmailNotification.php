<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;

class VerifyEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $code)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Verification Code')
            ->greeting('Hello ' . ($notifiable->name ?? 'User') . ',')
            ->line('Thank you for registering.')
            ->line('Your verification code is:')
            ->line("**{$this->code}**")
            ->line('This code will expire in 10 minutes.')
            ->line('If you did not create an account, no further action is required.')
            ->salutation('Regards, ' . Config::get('app.name'));
    }
}
