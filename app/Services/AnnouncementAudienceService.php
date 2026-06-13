<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class AnnouncementAudienceService
{
    public function applyVisibilityScope($query, mixed $user, string $announcementAlias = 'announcements'): void
    {
        $query->where(function ($audienceQuery) use ($announcementAlias) {
            $audienceColumn = $this->qualifiedColumn($announcementAlias, 'target_audience');

            $audienceQuery
                ->whereNull($audienceColumn)
                ->orWhereRaw("LOWER(COALESCE(NULLIF(TRIM({$audienceColumn}), ''), 'all')) IN ('all', 'alumni')");
        });

        $targetDepartment = $this->normalizeText(data_get($user, 'department'));
        $targetGraduationYear = $this->normalizeYear(data_get($user, 'year_graduated'));

        $departmentColumn = $this->qualifiedColumn($announcementAlias, 'target_department');
        $query->where(function ($departmentQuery) use ($departmentColumn, $targetDepartment) {
            $departmentQuery
                ->whereNull($departmentColumn)
                ->orWhereRaw("TRIM(COALESCE({$departmentColumn}, '')) = ''");

            if ($targetDepartment !== null) {
                $departmentQuery->orWhereRaw("LOWER({$departmentColumn}) = ?", [$targetDepartment]);
            }
        });

        $graduationYearColumn = $this->qualifiedColumn($announcementAlias, 'target_graduation_year');
        $query->where(function ($yearQuery) use ($graduationYearColumn, $targetGraduationYear) {
            $yearQuery->whereNull($graduationYearColumn);

            if ($targetGraduationYear !== null) {
                $yearQuery->orWhere($graduationYearColumn, $targetGraduationYear);
            }
        });
    }

    public function isVisibleToUser(object $announcement, mixed $user): bool
    {
        $targetAudience = $this->normalizeText(data_get($announcement, 'target_audience')) ?? 'all';

        if (!in_array($targetAudience, ['all', 'alumni'], true)) {
            return false;
        }

        $targetDepartment = $this->normalizeText(data_get($announcement, 'target_department'));
        $userDepartment = $this->normalizeText(data_get($user, 'department'));
        if ($targetDepartment !== null && $targetDepartment !== $userDepartment) {
            return false;
        }

        $targetGraduationYear = $this->normalizeYear(data_get($announcement, 'target_graduation_year'));
        $userGraduationYear = $this->normalizeYear(data_get($user, 'year_graduated'));

        return $targetGraduationYear === null || $targetGraduationYear === $userGraduationYear;
    }

    public function recipientsQuery(object|array $announcement): EloquentBuilder
    {
        $targetAudience = $this->normalizeText(data_get($announcement, 'target_audience')) ?? 'all';

        $query = User::query()
            ->alumni()
            ->registeredAlumniAccount()
            ->whereNotNull('email');

        if (!in_array($targetAudience, ['all', 'alumni'], true)) {
            return $query->whereRaw('1 = 0');
        }

        $targetDepartment = $this->normalizeText(data_get($announcement, 'target_department'));
        if ($targetDepartment !== null) {
            $query->whereRaw("LOWER(COALESCE(NULLIF(TRIM(department), ''), '')) = ?", [$targetDepartment]);
        }

        $targetGraduationYear = $this->normalizeYear(data_get($announcement, 'target_graduation_year'));
        if ($targetGraduationYear !== null) {
            $query->where('year_graduated', $targetGraduationYear);
        }

        return $query;
    }

    protected function qualifiedColumn(string $alias, string $column): string
    {
        return "{$alias}.{$column}";
    }

    protected function normalizeText(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $normalized = mb_strtolower(trim((string) $value));

        return $normalized === '' ? null : $normalized;
    }

    protected function normalizeYear(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $year = (int) $value;

        return $year > 0 ? $year : null;
    }
}
