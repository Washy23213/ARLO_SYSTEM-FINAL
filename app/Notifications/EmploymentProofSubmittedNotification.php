<?php

namespace App\Notifications;

use App\Models\TracerRecord;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmploymentProofSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected User $alumni,
        protected TracerRecord $record
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $alumniName = trim(($this->alumni->firstname ?? '') . ' ' . ($this->alumni->lastname ?? ''));
        $alumniName = $alumniName !== '' ? $alumniName : $this->alumni->email;

        return (new MailMessage)
            ->subject('Employment proof pending review')
            ->greeting('Hello ' . trim(($notifiable->firstname ?? '') . ' ' . ($notifiable->lastname ?? '')))
            ->line("{$alumniName} uploaded an employment proof PDF.")
            ->line('Please review the proof from the alumni details page before the account is displayed as employed.')
            ->action('Review Employment Proof', route('admin.alumni.show', $this->alumni->id));
    }

    public function toArray(object $notifiable): array
    {
        $alumniName = trim(($this->alumni->firstname ?? '') . ' ' . ($this->alumni->lastname ?? ''));
        $alumniName = $alumniName !== '' ? $alumniName : $this->alumni->email;

        return [
            'title' => 'Employment proof pending review',
            'summary' => "{$alumniName} uploaded an employment proof PDF.",
            'category' => 'Employment Proof',
            'priority' => 'High',
            'type' => 'employment_proof_review',
            'alumni_id' => (string) $this->alumni->id,
            'tracer_record_id' => (string) $this->record->id,
            'url' => route('admin.alumni.show', $this->alumni->id, false),
        ];
    }
}
