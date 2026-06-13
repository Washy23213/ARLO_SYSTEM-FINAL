<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class JobSubmissionRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $jobTitle,
        protected string $company,
        protected ?string $adminNotes = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $reason = trim((string) $this->adminNotes);

        return (new MailMessage)
            ->subject('Your job submission was rejected')
            ->greeting('Hello ' . trim(($notifiable->firstname ?? '') . ' ' . ($notifiable->lastname ?? '')))
            ->line("Your submitted job posting for {$this->jobTitle} at {$this->company} was rejected by the CMU OARL admin team.")
            ->line('Reason for rejection: ' . $reason)
            ->line('You can review the reason in the portal and submit a corrected opportunity again.')
            ->action('Back to Jobs', route('jobs'))
            ->line('If you need help, please contact the alumni office.');
    }

    public function toArray(object $notifiable): array
    {
        $reason = trim((string) $this->adminNotes);
        $summary = "Your job posting for {$this->jobTitle} at {$this->company} was rejected. Reason: {$reason}";

        return [
            'title' => 'Job submission rejected',
            'summary' => Str::limit($summary, 240),
            'category' => 'Job Submission',
            'status' => 'rejected',
            'reason' => $reason,
            'url' => route('jobs', [], false),
        ];
    }
}
