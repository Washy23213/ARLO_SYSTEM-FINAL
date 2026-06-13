<?php

namespace App\Services;

use App\Models\TracerRecord;
use App\Models\User;

class AlumniProfileSummaryService
{
    public function summaryFor(User $user): array
    {
        $latestTracer = $this->latestTracerFor($user);
        $profileChecklist = $this->profileChecklistFor($user, $latestTracer);

        return [
            'latestTracer' => $latestTracer,
            'profileChecklist' => $profileChecklist,
            'profilePercent' => $this->profilePercentFor($profileChecklist),
            'employmentStatus' => $this->employmentStatusLabel(
                $latestTracer?->employment_type,
                $user->employed_after_grad
            ),
        ];
    }

    public function latestTracerFor(User $user): ?TracerRecord
    {
        return $user->tracerRecords()
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->orderByDesc('tracer_date')
            ->first();
    }

    public function profileChecklistFor(User $user, ?TracerRecord $latestTracer): array
    {
        return [
            'Phone number' => filled($user->phone),
            'College' => filled($user->department),
            'Degree' => filled($user->degree),
            'Batch' => filled($user->year_graduated),
            'Location' => filled($user->location),
            'Employment summary' => filled($latestTracer?->employment_type) || filled($user->employed_after_grad),
            'Skills' => filled($user->skills),
            'Latest tracer record' => $latestTracer !== null,
        ];
    }

    public function profilePercentFor(array $profileChecklist): int
    {
        if (count($profileChecklist) === 0) {
            return 0;
        }

        return (int) round((collect($profileChecklist)->filter()->count() / count($profileChecklist)) * 100);
    }

    public function employmentStatusLabel(?string $employmentType, ?string $employedAfterGrad): string
    {
        $normalizedType = mb_strtolower(trim((string) $employmentType));

        return match (true) {
            in_array($normalizedType, ['full-time', 'part-time', 'employed', 'employee'], true) => 'Employed',
            in_array($normalizedType, ['self-employed', 'self employed'], true) => 'Self-Employed',
            $normalizedType === 'unemployed' => 'Unemployed',
            $employedAfterGrad === 'Yes' => 'Employed',
            $employedAfterGrad === 'No' => 'Unemployed',
            default => 'Unknown',
        };
    }
}
