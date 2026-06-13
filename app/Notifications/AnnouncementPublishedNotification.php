<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class AnnouncementPublishedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $announcementId,
        protected string $title,
        protected string $content,
        protected string $type,
        protected string $priority
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New CMU OARL announcement')
            ->greeting('Hello ' . trim(($notifiable->firstname ?? '') . ' ' . ($notifiable->lastname ?? '')))
            ->line("A new {$this->type} announcement has been published on CMU OARL.")
            ->line($this->title)
            ->line(Str::limit($this->content, 160))
            ->line('Priority: ' . $this->priority)
            ->action('View Announcement', route('announcement.detail', $this->announcementId))
            ->line('Stay updated with the latest alumni office announcements and opportunities.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'announcement_id' => $this->announcementId,
            'title' => $this->title,
            'summary' => Str::limit($this->content, 140),
            'type' => $this->type,
            'priority' => $this->priority,
            'category' => 'Announcement',
            'url' => route('announcement.detail', $this->announcementId, false),
        ];
    }
}
