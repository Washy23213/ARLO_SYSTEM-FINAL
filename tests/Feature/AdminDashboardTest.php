<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_displays_satisfaction_charts(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSeeText('Teaching Feedback', false);
        $response->assertSeeText('University Facilities', false);
        $response->assertSeeText('Overall Experience', false);
        $response->assertDontSeeText('Sign-Up Satisfaction Overview', false);
    }
}
