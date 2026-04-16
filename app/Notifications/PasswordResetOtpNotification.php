<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PasswordResetOtpNotification extends Notification
{
    public function __construct(
        private readonly string $otp,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Password Reset Code')
            ->greeting("Hello {$notifiable->name},")
            ->line('You requested a password reset code. Use the code below to reset your password.')
            ->line('')
            ->line("**{$this->otp}**")
            ->line('')
            ->line('This code expires in 10 minutes.')
            ->line('If you did not request a password reset, please ignore this email.')
            ->line('This code is confidential. Do not share it with anyone.');
    }
}
