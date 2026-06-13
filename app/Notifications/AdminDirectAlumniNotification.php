<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class AdminDirectAlumniNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $title,
        protected string $message,
        protected string $level,
        protected ?string $sentByName = null,
        protected ?string $sentByEmail = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $levelLabel = match ($this->level) {
            'warning' => 'Warning',
            'reminder' => 'Reminder',
            default => 'Notice',
        };

        return (new MailMessage)
            ->subject("CMU OARL {$levelLabel}: {$this->title}")
            ->greeting('Hello ' . trim(($notifiable->firstname ?? '') . ' ' . ($notifiable->lastname ?? '')))
            ->line($this->message)
            ->line($this->sentByName ? "Sent by: {$this->sentByName}" : 'Sent by the CMU OARL administrator.')
            ->action('Open Notifications', route('notifications.index'))
            ->line('You may also sign in to your CMU OARL alumni portal to view this notification.');
    }

    public function toArray(object $notifiable): array
    {
        $levelLabel = match ($this->level) {
            'warning' => 'Warning',
            'reminder' => 'Reminder',
            default => 'Notice',
        };

        $priority = match ($this->level) {
            'warning' => 'High',
            'reminder' => 'Medium',
            default => 'Low',
        };

        return [
            'title' => $this->title,
            'summary' => Str::limit($this->message, 260),
            'message' => $this->message,
            'type' => $this->level === 'warning' ? 'alert' : 'update',
            'priority' => $priority,
            'status' => $levelLabel,
            'category' => 'Admin Notice',
            'url' => route('notifications.index', [], false),
            'sent_by_name' => $this->sentByName,
            'sent_by_email' => $this->sentByEmail,
        ];
    }
}
