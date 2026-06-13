<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AdminControllerSupport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateFeedbackRequest;
use App\Models\Admin;
use App\Services\AuditLogService;
use App\Support\PortalIndicatorState;
use App\Support\SystemOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminFeedbackController extends Controller
{
    use AdminControllerSupport;

    public function index(Request $request)
    {
        $this->ensureAdmin();
        $this->markFeedbackSeen($request);

        $search = trim((string) $request->query('search', ''));
        $selectedStatus = trim((string) $request->query('status', ''));
        $selectedPriority = trim((string) $request->query('priority', ''));
        $selectedType = trim((string) $request->query('type', ''));
        $selectedAssignee = trim((string) $request->query('assignee', ''));

        $feedbackItemsQuery = $this->feedbackBaseQuery();

        if ($search !== '') {
            $this->applyCaseInsensitiveSearch(
                $feedbackItemsQuery,
                $search,
                [
                    'f.subject',
                    'f.message',
                    'f.type',
                    'f.status',
                    'f.priority',
                    'submitter.email',
                    'assignee.email',
                    'submitter.firstname',
                    'submitter.lastname',
                    'assignee.firstname',
                    'assignee.lastname',
                ],
                [
                    ['submitter.firstname', 'submitter.lastname'],
                    ['assignee.firstname', 'assignee.lastname'],
                ]
            );
        }

        if ($selectedStatus !== '' && in_array($selectedStatus, SystemOptions::feedbackStatuses(), true)) {
            $feedbackItemsQuery->where('f.status', $selectedStatus);
        }

        if ($selectedPriority !== '' && in_array($selectedPriority, SystemOptions::feedbackPriorities(), true)) {
            $feedbackItemsQuery->where('f.priority', $selectedPriority);
        }

        if ($selectedType !== '' && in_array($selectedType, SystemOptions::feedbackTypes(), true)) {
            $feedbackItemsQuery->where('f.type', $selectedType);
        }

        if ($selectedAssignee === 'unassigned') {
            $feedbackItemsQuery->whereNull('f.assigned_to');
        } elseif ($selectedAssignee !== '') {
            $feedbackItemsQuery->where('f.assigned_to', $selectedAssignee);
        }

        $feedbackItems = $feedbackItemsQuery
            ->orderByRaw("
                CASE f.status
                    WHEN 'Open' THEN 0
                    WHEN 'In Progress' THEN 1
                    WHEN 'Resolved' THEN 2
                    WHEN 'Closed' THEN 3
                    ELSE 4
                END
            ")
            ->orderByRaw("
                CASE f.priority
                    WHEN 'High' THEN 0
                    WHEN 'Medium' THEN 1
                    WHEN 'Low' THEN 2
                    ELSE 3
                END
            ")
            ->orderByDesc('f.created_at')
            ->paginate(20)
            ->withQueryString();

        $statusOptions = SystemOptions::feedbackStatuses();
        $priorityOptions = SystemOptions::feedbackPriorities();
        $typeOptions = SystemOptions::feedbackTypes();
        $assigneeOptions = $this->adminAssigneeOptions();
        $summary = $this->summary();

        return view('admin.feedback-management', compact(
            'feedbackItems',
            'search',
            'selectedStatus',
            'selectedPriority',
            'selectedType',
            'selectedAssignee',
            'statusOptions',
            'priorityOptions',
            'typeOptions',
            'assigneeOptions',
            'summary'
        ));
    }

    public function show(Request $request, string $id)
    {
        $this->ensureAdmin();
        $this->markFeedbackSeen($request);

        $feedback = $this->feedbackBaseQuery()
            ->where('f.id', $id)
            ->first();

        abort_if($feedback === null, 404, 'Feedback not found.');

        $statusOptions = SystemOptions::feedbackStatuses();
        $priorityOptions = SystemOptions::feedbackPriorities();
        $assigneeOptions = $this->adminAssigneeOptions();

        return view('admin.feedback-show', compact(
            'feedback',
            'statusOptions',
            'priorityOptions',
            'assigneeOptions'
        ));
    }

    public function update(UpdateFeedbackRequest $request, string $id)
    {
        $this->ensureAdmin();

        $feedback = DB::table('feedback')->where('id', $id)->first();

        if (!$feedback) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Feedback record not found.',
                ], 404);
            }

            return redirect()->route('admin.feedback')->with('error', 'Feedback record not found.');
        }

        $validated = $request->validated();
        $status = (string) $validated['status'];
        $priority = (string) $validated['priority'];
        $assignedTo = $validated['assigned_to'] ?? null;

        $updatedAt = now();
        $resolvedAt = in_array($status, ['Resolved', 'Closed'], true)
            ? (!empty($feedback->resolved_at) ? \Illuminate\Support\Carbon::parse($feedback->resolved_at) : $updatedAt->copy())
            : null;

        $updateData = [
            'status' => $status,
            'priority' => $priority,
            'assigned_to' => $assignedTo,
            'updated_at' => $updatedAt,
        ];

        $updateData['resolved_at'] = $resolvedAt;

        DB::table('feedback')->where('id', $id)->update($updateData);

        app(AuditLogService::class)->log(
            (string) auth()->id(),
            'admin.feedback.update',
            "Updated feedback monitoring state for {$id} to {$status}.",
            $request
        );

        if ($request->expectsJson()) {
            $assignedAdmin = $assignedTo ? Admin::query()->find($assignedTo, ['firstname', 'lastname', 'email']) : null;
            $assignedLabel = 'Unassigned';

            if ($assignedAdmin) {
                $assignedName = trim((string) $assignedAdmin->firstname . ' ' . (string) $assignedAdmin->lastname);
                $assignedLabel = $assignedName !== '' ? $assignedName : ($assignedAdmin->email ?? 'Unassigned');
            }

            return response()->json([
                'message' => 'Feedback monitoring details updated successfully.',
                'feedback' => [
                    'statusLabel' => $status,
                    'priorityLabel' => $priority,
                    'assignedToLabel' => $assignedLabel,
                    'resolvedAtLabel' => $resolvedAt ? $resolvedAt->format('F j, Y h:i A') : 'Not resolved yet',
                    'updatedAtLabel' => $updatedAt->format('F j, Y h:i A'),
                ],
            ]);
        }

        return redirect()
            ->route('admin.feedback.show', $id)
            ->with('success', 'Feedback monitoring details updated successfully.');
    }

    protected function feedbackBaseQuery()
    {
        return DB::table('feedback as f')
            ->leftJoin('users as submitter', 'submitter.id', '=', 'f.user_id')
            ->leftJoin('admins as assignee', 'assignee.id', '=', 'f.assigned_to')
            ->select(
                'f.id',
                'f.subject',
                'f.message',
                'f.type',
                'f.status',
                'f.priority',
                'f.user_id',
                'f.assigned_to',
                'f.resolved_at',
                'f.created_at',
                'f.updated_at',
                'submitter.firstname as submitter_firstname',
                'submitter.lastname as submitter_lastname',
                'submitter.email as submitter_email',
                'assignee.firstname as assignee_firstname',
                'assignee.lastname as assignee_lastname',
                'assignee.email as assignee_email'
            );
    }

    protected function adminAssigneeOptions()
    {
        return Admin::query()
            ->orderBy('firstname')
            ->orderBy('lastname')
            ->get(['id', 'firstname', 'lastname', 'email'])
            ->map(function (Admin $admin) {
                $name = trim($admin->firstname . ' ' . $admin->lastname);

                return [
                    'id' => (string) $admin->id,
                    'label' => $name !== '' ? $name : $admin->email,
                    'email' => $admin->email,
                ];
            });
    }

    protected function summary(): array
    {
        $activeStatuses = ['Open', 'In Progress'];
        $activeFeedback = DB::table('feedback')->whereIn('status', $activeStatuses)->count();
        $unassignedFeedback = DB::table('feedback')
            ->whereIn('status', $activeStatuses)
            ->whereNull('assigned_to')
            ->count();
        $urgentFeedback = DB::table('feedback')
            ->whereIn('status', $activeStatuses)
            ->where('priority', 'High')
            ->count();
        $solvedFeedback = DB::table('feedback')->whereIn('status', ['Resolved', 'Closed'])->count();

        return [
            'totalFeedback' => DB::table('feedback')->count(),
            'openFeedback' => DB::table('feedback')->where('status', 'Open')->count(),
            'inProgressFeedback' => DB::table('feedback')->where('status', 'In Progress')->count(),
            'activeFeedback' => $activeFeedback,
            'unassignedOpenFeedback' => DB::table('feedback')->where('status', 'Open')->whereNull('assigned_to')->count(),
            'unassignedFeedback' => $unassignedFeedback,
            'highPriorityOpenFeedback' => $urgentFeedback,
            'urgentFeedback' => $urgentFeedback,
            'handledFeedback' => $solvedFeedback,
            'solvedFeedback' => $solvedFeedback,
        ];
    }

    protected function markFeedbackSeen(Request $request): void
    {
        PortalIndicatorState::markSeen(
            $request,
            PortalIndicatorState::ADMIN_FEEDBACK,
            $request->user()?->getAuthIdentifier()
        );
    }
}
