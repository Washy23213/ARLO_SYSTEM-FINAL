<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\SuperAdmin;
use App\Models\TracerRecord;
use App\Models\User;
use App\Notifications\EmploymentProofSubmittedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Throwable;

class EmploymentProofReviewNotificationService
{
    public function notifyPendingReview(User $alumni, TracerRecord $record): int
    {
        if (! $record->hasEmploymentProof()) {
            return 0;
        }

        $recipients = $this->reviewRecipients();

        if ($recipients->isEmpty()) {
            return 0;
        }

        $count = 0;

        foreach ($recipients as $recipient) {
            try {
                Notification::sendNow($recipient, new EmploymentProofSubmittedNotification($alumni, $record));
                $count++;
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $count;
    }

    protected function reviewRecipients(): Collection
    {
        $admins = Admin::query()
            ->get()
            ->filter(fn (Admin $admin): bool => $admin->hasPermission(Admin::PERMISSION_MANAGE_ALUMNI));

        return $admins
            ->concat(SuperAdmin::query()->get())
            ->unique(fn (object $notifiable): string => $notifiable::class . ':' . $notifiable->getKey())
            ->values();
    }
}
