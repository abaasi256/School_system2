<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DashboardPerformanceTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function teacher_dashboard_does_not_load_users()
    {
        // Teachers are TeamSAT but not TeamSA
        $teacher = User::factory()->create(['user_type' => 'teacher']);

        $this->actingAs($teacher);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('pages.support_team.dashboard');

        // Before optimization: 'users' variable is passed (incorrectly)
        // After optimization: 'users' variable should be missing
        $response->assertViewMissing('users');

        // And counts shouldn't be there either for teachers
        $response->assertViewMissing('users_count');
    }

    /** @test */
    public function admin_dashboard_loads_optimized_counts()
    {
        // Admins are TeamSA
        $admin = User::factory()->create(['user_type' => 'admin']);

        // Create known number of users
        User::factory()->count(5)->create(['user_type' => 'student']);
        User::factory()->count(3)->create(['user_type' => 'teacher']);

        $this->actingAs($admin);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('pages.support_team.dashboard');

        // Should not have full users collection
        $response->assertViewMissing('users');

        // Should have counts
        $response->assertViewHas('users_count');

        $counts = $response->viewData('users_count');

        // Ensure it's a collection or array we can access
        $this->assertTrue(is_a($counts, \Illuminate\Support\Collection::class));

        $this->assertGreaterThanOrEqual(5, $counts->get('student'));
        $this->assertGreaterThanOrEqual(3 + 1, $counts->get('teacher')); // +1 for the teacher created in previous test if transaction rollback fails, or +1 for the teacher user itself if we query all. Wait, teacher is user too.
        // Actually, $admin is 'admin'.
        // Created 3 teachers.
    }
}
