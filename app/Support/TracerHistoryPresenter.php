<?php

namespace App\Support;

use App\Models\TracerRecord;
use Illuminate\Support\Collection;

class TracerHistoryPresenter
{
    public static function present(Collection $history): Collection
    {
        $history = self::visible($history)->values();

        return $history->map(function (TracerRecord $record, int $index) use ($history) {
            $olderRecord = $history->get($index + 1);
            $comparison = self::buildHistoryComparison($record, $olderRecord);

            return [
                'record' => $record,
                'badges' => $comparison['badges'],
                'note' => $comparison['note'],
            ];
        });
    }

    public static function visible(Collection $history): Collection
    {
        $history = $history->values();

        if ($history->isEmpty()) {
            return collect();
        }

        $visibleHistory = collect();
        $run = collect();
        $previousSignature = null;

        foreach ($history as $record) {
            $signature = self::historySummarySignature($record);

            if ($previousSignature === null || $signature === $previousSignature) {
                $run->push($record);
            } else {
                $visibleHistory->push($run->last());
                $run = collect([$record]);
            }

            $previousSignature = $signature;
        }

        if ($run->isNotEmpty()) {
            $visibleHistory->push($run->last());
        }

        return $visibleHistory->values();
    }

    protected static function buildHistoryComparison(TracerRecord $record, ?TracerRecord $olderRecord): array
    {
        $badges = [];

        if (!$olderRecord) {
            $badges[] = self::historyBadge('Baseline Record', 'slate');

            return [
                'badges' => $badges,
                'note' => 'This is the earliest tracer entry on file.',
            ];
        }

        $currentCompany = self::normalizedTracerValue($record->current_company);
        $olderCompany = self::normalizedTracerValue($olderRecord->current_company);
        $currentPosition = self::normalizedTracerValue($record->current_position);
        $olderPosition = self::normalizedTracerValue($olderRecord->current_position);
        $currentIndustry = self::normalizedTracerValue($record->industry);
        $olderIndustry = self::normalizedTracerValue($olderRecord->industry);
        $currentStatus = self::normalizedTracerValue($record->employment_type);
        $olderStatus = self::normalizedTracerValue($olderRecord->employment_type);
        $currentEducation = self::normalizedTracerValue($record->education_level);
        $olderEducation = self::normalizedTracerValue($olderRecord->education_level);

        if ($currentCompany && $olderCompany && $currentCompany === $olderCompany) {
            $badges[] = self::historyBadge('Same Company', 'sky');
        } elseif ($currentCompany && $currentCompany !== $olderCompany) {
            $badges[] = self::historyBadge('New Company', 'emerald');
        } elseif (!$currentCompany && $olderCompany) {
            $badges[] = self::historyBadge('Company Removed', 'rose');
        }

        if ($currentPosition && $currentPosition !== $olderPosition) {
            $badges[] = self::historyBadge('New Position', 'violet');
        }

        if ($currentIndustry && $currentIndustry !== $olderIndustry) {
            $badges[] = self::historyBadge('New Industry', 'amber');
        }

        if ($currentStatus && $currentStatus !== $olderStatus) {
            $badges[] = self::historyBadge('Status Changed', 'slate');
        }

        if (
            ((bool) $record->further_education !== (bool) $olderRecord->further_education)
            || ($currentEducation !== $olderEducation)
        ) {
            $badges[] = self::historyBadge('Education Updated', 'violet');
        }

        if ($badges === []) {
            $badges[] = self::historyBadge('No Major Change', 'slate');
        }

        return [
            'badges' => $badges,
            'note' => 'Compared with the ' . ($olderRecord->tracer_date?->format('M d, Y') ?? 'older') . ' entry.',
        ];
    }

    protected static function historySummarySignature(TracerRecord $record): string
    {
        return implode('|', [
            self::normalizedTracerValue($record->current_company) ?? '',
            self::normalizedTracerValue($record->current_position) ?? '',
            self::normalizedTracerValue($record->industry) ?? '',
            self::normalizedTracerValue($record->employment_proof_path) ?? '',
            self::normalizedTracerValue($record->employmentProofStatus()) ?? '',
            $record->further_education ? '1' : '0',
            self::normalizedTracerValue($record->education_level) ?? '',
        ]);
    }

    protected static function normalizedTracerValue(?string $value): ?string
    {
        $clean = SystemOptions::clean($value);

        return $clean === null ? null : mb_strtolower($clean);
    }

    protected static function historyBadge(string $label, string $tone): array
    {
        $classes = match ($tone) {
            'emerald' => 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200',
            'sky' => 'bg-sky-100 text-sky-700 ring-1 ring-sky-200',
            'amber' => 'bg-amber-100 text-amber-700 ring-1 ring-amber-200',
            'violet' => 'bg-violet-100 text-violet-700 ring-1 ring-violet-200',
            'rose' => 'bg-rose-100 text-rose-700 ring-1 ring-rose-200',
            default => 'bg-slate-100 text-slate-700 ring-1 ring-slate-200',
        };

        return [
            'label' => $label,
            'classes' => $classes,
        ];
    }
}
