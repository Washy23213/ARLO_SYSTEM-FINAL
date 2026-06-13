<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;

trait AdminControllerSupport
{
    protected function applyCaseInsensitiveSearch($query, string $search, array $columns, array $tokenGroups = []): void
    {
        $normalizedSearch = trim($search);

        if ($normalizedSearch === '') {
            return;
        }

        $likeTerm = $this->caseInsensitiveLikeTerm($normalizedSearch);

        $query->where(function ($searchQuery) use ($columns, $tokenGroups, $likeTerm, $normalizedSearch) {
            foreach ($columns as $column) {
                $this->orWhereCaseInsensitiveContains($searchQuery, $column, $likeTerm);
            }

            foreach ($tokenGroups as $tokenGroup) {
                $this->orWhereTokenizedCaseInsensitiveMatch($searchQuery, $normalizedSearch, $tokenGroup);
            }
        });
    }

    protected function tableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }

    protected function columnExists(string $table, string $column): bool
    {
        return $this->tableExists($table) && Schema::hasColumn($table, $column);
    }

    protected function safeRate(int $part, int $total): float
    {
        if ($total === 0) {
            return 0;
        }

        return round(($part / $total) * 100, 1);
    }

    protected function ensureAdmin(): void
    {
        $superAdmin = Auth::guard('super_admin')->user();

        if ($superAdmin) {
            Auth::shouldUse('super_admin');

            return;
        }

        $admin = Auth::guard('admin')->user();

        if ($admin) {
            Auth::shouldUse('admin');

            return;
        }

        if (!auth()->check() || ! auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }
    }

    protected function adminOwnedActorId(): ?string
    {
        if (Auth::guard('super_admin')->check()) {
            return null;
        }

        $adminId = Auth::guard('admin')->id();

        if ($adminId !== null) {
            return (string) $adminId;
        }

        $user = Auth::guard('web')->user();

        return $user && $user->isAdmin()
            ? (string) $user->getAuthIdentifier()
            : null;
    }

    protected function caseInsensitiveLikeTerm(string $value): string
    {
        return '%' . mb_strtolower(trim($value)) . '%';
    }

    protected function orWhereCaseInsensitiveContains($query, string $column, string $likeTerm): void
    {
        $query->orWhereRaw("LOWER({$column}) LIKE ?", [$likeTerm]);
    }

    protected function orWhereTokenizedCaseInsensitiveMatch($query, string $search, array $columns): void
    {
        $tokens = array_values(array_filter(
            preg_split('/\s+/', trim($search)) ?: [],
            static fn (string $token): bool => $token !== ''
        ));

        if ($tokens === []) {
            return;
        }

        $query->orWhere(function ($tokenQuery) use ($tokens, $columns) {
            foreach ($tokens as $token) {
                $tokenLikeTerm = $this->caseInsensitiveLikeTerm($token);

                $tokenQuery->where(function ($columnQuery) use ($columns, $tokenLikeTerm) {
                    foreach ($columns as $column) {
                        $this->orWhereCaseInsensitiveContains($columnQuery, $column, $tokenLikeTerm);
                    }
                });
            }
        });
    }
}
