<?php

namespace App\Http\Controllers\Alumni;

use App\Http\Controllers\Controller;
use App\Http\Requests\Alumni\SubmitFeedbackRequest;
use App\Services\AuditLogService;
use App\Support\SystemOptions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AlumniFeedbackController extends Controller
{
    public function feedbackForm()
    {
        return view('feedback', [
            'feedbackCategories' => $this->feedbackCategories(),
        ]);
    }

    public function submitFeedback(SubmitFeedbackRequest $request)
    {
        if (!Schema::hasTable('feedback')) {
            return redirect()->route('feedback.form')->withErrors(['feedback' => 'Feedback table is not ready yet.']);
        }

        $validated = $request->validated();
        $feedbackType = $validated['type'] ?? 'General Feedback';

        $feedbackId = (string) Str::uuid();

        DB::table('feedback')->insert([
            'id' => $feedbackId,
            'user_id' => auth()->id(),
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'type' => $feedbackType,
            'status' => 'Open',
            'priority' => 'Medium',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(AuditLogService::class)->log(
            (string) auth()->id(),
            'alumni.feedback.submit',
            "Submitted {$feedbackType} feedback: {$validated['subject']} ({$feedbackId}).",
            $request
        );

        return redirect()->route('feedback.form')->with('success', 'Feedback submitted successfully.');
    }

    protected function feedbackCategories(): array
    {
        $details = [
            'Bug Report' => [
                'title' => 'Report a portal problem',
                'description' => 'Use this when a page is broken, a button does not work, or data appears incorrect.',
                'subject_placeholder' => 'Example: Tracer form does not save',
                'message_placeholder' => 'Tell us what page you opened, what you clicked, what happened, and what you expected instead.',
                'tip' => 'Best for errors, failed submissions, missing data, or broken actions.',
            ],
            'Feature Request' => [
                'title' => 'Suggest an improvement',
                'description' => 'Use this when you want a new feature, a better workflow, or a more helpful tool in the portal.',
                'subject_placeholder' => 'Example: Add search to notifications',
                'message_placeholder' => 'Describe the improvement you want, why it would help alumni, and where it should appear.',
                'tip' => 'Best for new ideas, usability improvements, and future enhancements.',
            ],
            'General Feedback' => [
                'title' => 'Share a general comment',
                'description' => 'Use this for overall impressions, comments about your experience, or non-urgent suggestions.',
                'subject_placeholder' => 'Example: Feedback on the alumni dashboard',
                'message_placeholder' => 'Share your experience clearly and mention what is working well or what feels confusing.',
                'tip' => 'Best for comments about clarity, design, content, and overall user experience.',
            ],
            'Support' => [
                'title' => 'Ask for help',
                'description' => 'Use this when you need account assistance, guidance on using the portal, or help completing a task.',
                'subject_placeholder' => 'Example: Need help updating my profile',
                'message_placeholder' => 'Explain the help you need, what you already tried, and any account or access issue involved.',
                'tip' => 'Best for login issues, account concerns, and questions about how to use the portal.',
            ],
        ];

        return array_map(function (string $type) use ($details) {
            return [
                'value' => $type,
                ...($details[$type] ?? [
                    'title' => $type,
                    'description' => 'Share details so the alumni office can review it properly.',
                    'subject_placeholder' => 'Brief summary',
                    'message_placeholder' => 'Add more details here.',
                    'tip' => 'Choose the option that best matches your concern.',
                ]),
            ];
        }, SystemOptions::feedbackTypes());
    }
}
