<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class JobSubmissionApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $jobTitle,
        protected string $company,
        protected string $jobId
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your job submission was approved')
            ->greeting('Hello ' . trim(($notifiable->firstname ?? '') . ' ' . ($notifiable->lastname ?? '')))
            ->line("Your submitted job posting for {$this->jobTitle} at {$this->company} has been approved by the CMU OARL admin team.")
            ->line('It is now visible on the alumni job board.')
            ->action('View Posted Job', route('job.detail', $this->jobId))
            ->line('Thank you for contributing job opportunities to the alumni community.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Job submission approved',
            'summary' => Str::limit("Your job posting for {$this->jobTitle} at {$this->company} is now live on the alumni job board.", 140),
            'category' => 'Job Submission',
            'status' => 'approved',
            'url' => route('job.detail', $this->jobId, false),
        ];
    }
}
