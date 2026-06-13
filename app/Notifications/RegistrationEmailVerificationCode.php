<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationEmailVerificationCode extends Notification
{
    use Queueable;

    public function __construct(
        public string $code,
        public int $expiresInMinutes
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('CMU OARL registration email verification')
            ->greeting('Hello')
            ->line("Your CMU OARL registration verification code is {$this->code}.")
            ->line("This code expires in {$this->expiresInMinutes} minutes.")
            ->line('If you did not request this code, you can ignore this email.');
    }
}
