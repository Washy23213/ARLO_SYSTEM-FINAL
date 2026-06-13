<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AdminControllerSupport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminAnalyticsFiltersRequest;
use App\Services\AdminAnalyticsService;
use App\Services\AuditLogService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminAnalyticsController extends Controller
{
    use AdminControllerSupport;

    public function analytics(AdminAnalyticsFiltersRequest $request, AdminAnalyticsService $analyticsService)
    {
        $this->ensureAdmin();

        $payload = $analyticsService->analyticsPayload($request->filters(), $request->forceRefresh());

        if ($request->expectsJson()) {
            return response()->json([
                'html' => view('admin.partials.analytics-content', $payload)->render(),
                'filters' => $payload['filters'],
                'ui' => $payload['ui'],
                'refreshed' => $request->forceRefresh(),
            ]);
        }

        return view('admin.analytics', $payload);
    }

    public function exportCsv(
        string $report,
        AdminAnalyticsFiltersRequest $request,
        AdminAnalyticsService $analyticsService
    ): StreamedResponse {
        $this->ensureAdmin();

        $export = match ($report) {
            'employability' => $analyticsService->employabilityCsvExport($request->filters()),
            'satisfaction' => $analyticsService->satisfactionCsvExport($request->filters()),
            default => abort(404),
        };

        app(AuditLogService::class)->log(
            (string) $request->user()?->getAuthIdentifier(),
            'admin.analytics.export',
            "Exported {$report} analytics report as {$export['filename']}.",
            $request
        );

        return response()->streamDownload(function () use ($export) {
            echo "\xEF\xBB\xBF";

            $handle = fopen('php://output', 'w');

            foreach ($export['rows'] as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $export['filename'], [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }
}
