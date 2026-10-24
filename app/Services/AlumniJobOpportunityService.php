<?php

namespace App\Services;

use App\Support\SystemOptions;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlumniJobOpportunityService
{
    /**
     * Return the same active, non-archived jobs for both the dashboard and job board.
     */
    public function latest(int $limit = 3)
    {
        $query = $this->activeJobsQuery([
            'id',
            'title',
            'company',
            'type',
            'location',
            'created_at',
        ]);

        return $query
            ? $query->orderByDesc('created_at')->limit($limit)->get()
            : collect();
    }

    public function paginateForBoard(Request $request, int $perPage = 10): LengthAwarePaginator
    {
        $page = max(1, (int) $request->query('page', 1));

        if (!Schema::hasTable('jobs')) {
            return new LengthAwarePaginator([], 0, $perPage, $page, [
                'path' => $request->url(),
                'pageName' => 'page',
            ]);
        }

        $columns = [
            'id',
            'title',
            'company',
            'description',
            'location',
            'type',
            'created_at',
            'updated_at',
        ];

        foreach (['application_deadline', 'views_count'] as $optionalColumn) {
            if (Schema::hasColumn('jobs', $optionalColumn)) {
                $columns[] = $optionalColumn;
            }
        }

        $query = $this->activeJobsQuery($columns);

        if (!$query) {
            return new LengthAwarePaginator([], 0, $perPage, $page, [
                'path' => $request->url(),
                'pageName' => 'page',
            ]);
        }

        $search = mb_substr(trim((string) $request->query('q', '')), 0, 120);
        if ($search !== '') {
            $pattern = '%' . mb_strtolower($search) . '%';
            $query->where(function (Builder $builder) use ($pattern) {
                foreach (['title', 'company', 'location', 'type', 'description'] as $column) {
                    $builder->orWhereRaw('LOWER(' . $column . ') LIKE ?', [$pattern]);
                }
            });
        }

        $type = trim((string) $request->query('type', ''));
        if (in_array($type, SystemOptions::jobTypes(), true)) {
            $query->where('type', $type);
        }

        $location = trim((string) $request->query('location', ''));
        if (in_array($location, SystemOptions::locations(), true)) {
            $query->where('location', $location);
        }

        return $query->orderByDesc('created_at')->paginate($perPage)->withQueryString();
    }

    /** @param array<int, string> $columns */
    protected function activeJobsQuery(array $columns): ?Builder
    {
        if (!Schema::hasTable('jobs')) {
            return null;
        }

        $query = DB::table('jobs')->select(array_values(array_unique($columns)));

        if (Schema::hasColumn('jobs', 'archived_at')) {
            $query->whereNull('archived_at');
        }

        if (Schema::hasColumn('jobs', 'is_active')) {
            $query->where('is_active', true);
        }

        return $query;
    }
}
