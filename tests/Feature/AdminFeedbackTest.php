<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminFeedbackTest extends TestCase
{
    use RefreshDatabase;

    protected function adminUser(array $attributes = []): User
    {
        return User::factory()->admin()->create($attributes);
    }

    public function test_admin_can_view_and_filter_feedback_monitoring_page(): void
    {
        $admin = $this->adminUser();
        $sender = User::factory()->create([
            'firstname' => 'Janna',
            'lastname' => 'Rivera',
            'email' => 'janna.rivera@example.com',
        ]);

        DB::table('feedback')->insert([
            [
                'id' => (string) Str::uuid(),
                'user_id' => $sender->id,
                'subject' => 'Portal suggestion',
                'message' => 'Please add a saved jobs panel for alumni users.',
                'type' => 'Feature Request',
                'status' => 'Open',
                'priority' => 'High',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'user_id' => $sender->id,
                'subject' => 'Resolved login issue',
                'message' => 'This issue was already handled and confirmed by the team.',
                'type' => 'Support',
                'status' => 'Resolved',
                'priority' => 'Low',
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.feedback', [
            'search' => 'saved jobs',
            'status' => 'Open',
            'priority' => 'High',
            'type' => 'Feature Request',
        ]));

        $response->assertOk();
        $response->assertSee('Feedback Monitoring');
        $response->assertSee('Open Feedback');
        $response->assertSee('No Admin Assigned');
        $response->assertSee('Urgent Feedback');
        $response->assertSee('Solved Feedback');
        $response->assertSee('Portal suggestion');
        $response->assertDontSee('Resolved login issue');
        $response->assertViewHas('summary', function (array $summary): bool {
            return ($summary['activeFeedback'] ?? null) === 1
                && ($summary['unassignedFeedback'] ?? null) === 1
                && ($summary['urgentFeedback'] ?? null) === 1
                && ($summary['solvedFeedback'] ?? null) === 1;
        });
    }

    public function test_admin_can_review_and_update_feedback_monitoring_details(): void
    {
        $admin = $this->adminUser([
            'firstname' => 'Mila',
            'lastname' => 'Torres',
            'email' => 'mila.torres@example.com',
        ]);
        $sender = User::factory()->create();
        $feedbackId = (string) Str::uuid();

        DB::table('feedback')->insert([
            'id' => $feedbackId,
            'user_id' => $sender->id,
            'subject' => 'Tracer timing',
            'message' => 'The tracer reminder schedule could be easier to understand.',
            'type' => 'General Feedback',
            'status' => 'Open',
            'priority' => 'Medium',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $showResponse = $this->actingAs($admin)->get(route('admin.feedback.show', $feedbackId));

        $showResponse->assertOk();
        $showResponse->assertSee('Tracer timing');
        $showResponse->assertSee('Save changes');

        $updateResponse = $this->actingAs($admin)->put(route('admin.feedback.update', $feedbackId), [
            'status' => 'Resolved',
            'priority' => 'High',
            'assigned_to' => $admin->id,
        ]);

        $updateResponse->assertRedirect(route('admin.feedback.show', $feedbackId));
        $updateResponse->assertSessionHas('success', 'Feedback monitoring details updated successfully.');
        $this->assertDatabaseHas('feedback', [
            'id' => $feedbackId,
            'status' => 'Resolved',
            'priority' => 'High',
            'assigned_to' => $admin->id,
        ]);
        $this->assertNotNull(DB::table('feedback')->where('id', $feedbackId)->value('resolved_at'));
        $this->assertDatabaseHas('system_logs', [
            'user_id' => $admin->id,
            'action' => 'admin.feedback.update',
        ]);
    }

    public function test_admin_feedback_update_can_return_json_for_inline_editing(): void
    {
        $admin = $this->adminUser([
            'firstname' => 'Mila',
            'lastname' => 'Torres',
            'email' => 'mila.torres@example.com',
        ]);
        $sender = User::factory()->create();
        $feedbackId = (string) Str::uuid();

        DB::table('feedback')->insert([
            'id' => $feedbackId,
            'user_id' => $sender->id,
            'subject' => 'Tracer timing',
            'message' => 'The tracer reminder schedule could be easier to understand.',
            'type' => 'General Feedback',
            'status' => 'Open',
            'priority' => 'Medium',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->put(route('admin.feedback.update', $feedbackId), [
                'status' => 'Resolved',
                'priority' => 'High',
                'assigned_to' => $admin->id,
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Feedback monitoring details updated successfully.',
                'feedback' => [
                    'statusLabel' => 'Resolved',
                    'priorityLabel' => 'High',
                    'assignedToLabel' => 'Mila Torres',
                ],
            ]);

        $this->assertDatabaseHas('feedback', [
            'id' => $feedbackId,
            'status' => 'Resolved',
            'priority' => 'High',
            'assigned_to' => $admin->id,
        ]);
        $this->assertNotNull(DB::table('feedback')->where('id', $feedbackId)->value('resolved_at'));
    }
}
