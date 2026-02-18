<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Config;

class VerifyEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $token)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verify Your Email Address')
            ->greeting('Hello ' . ($notifiable->name ?? 'User') . ',')
            ->line('Thank you for registering.')
            ->line('Please click the button below to verify your email address.')
            ->action('Verify Email', $this->verificationUrl())
            ->line('This link will expire in 24 hours.')
            ->salutation('Regards, ' . Config::get('app.name'));
    }

    protected function verificationUrl(): string
    {
        return URL::temporarySignedRoute(
            'verification.email', // web.php route
            Carbon::now()->addHours(24),
            [
                'token' => $this->token,
            ]
        );
    }
}
