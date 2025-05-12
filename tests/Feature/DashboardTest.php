<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('citizen users see the citizen dashboard', function () {
    $user = User::factory()->create(['role' => 'citizen']);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertInertia(function (Assert $page) {
        $page->component('Dashboard/Citizen')
            ->has('openBills')
            ->has('recentSubmissions')
            ->has('notifications')
            ->has('stats', fn (Assert $stats) => $stats->has('openBills')->has('totalSubmissions')->has('pendingReviews'))
            ->has('upcomingDeadlines')
            ->has('topicHighlights')
            ->has('resourceShortcuts')
            ->has('supportChannels')
            ->has('knowledgeBase')
            ->has('communityClinics')
            ->has('faqs');
    });
});

test('mp users see the legislator dashboard', function () {
    $user = User::factory()->create([
        'role' => 'mp',
        'legislative_house' => 'national_assembly',
    ]);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertInertia(fn (Assert $page) => $page->component('Dashboard/Legislator')
        ->has('topBills')
        ->has('summaries')
        ->has('submissionBreakdown')
        ->has('clauseHighlights')
        ->has('reportLinks'));
});

test('clerks see the clerk dashboard', function () {
    $user = User::factory()->create(['role' => 'clerk']);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertInertia(fn (Assert $page) => $page->component('Dashboard/Clerk')
        ->has('recentBills')
        ->has('recentSubmissions')
        ->has('submissionTypes')
        ->has('userMetrics'));
});

test('admins see the admin dashboard', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertInertia(fn (Assert $page) => $page->component('Dashboard/Admin')
        ->has('metrics', fn (Assert $metrics) => $metrics
            ->has('users')
            ->has('bills')
            ->has('submissions')
            ->has('sessions')
        )
        ->has('dailySubmissions')
        ->has('recentUsers')
        ->has('recentBills')
        ->has('recentSubmissions')
        ->has('recentSessions')
        ->has('systemAlerts')
        ->has('managementShortcuts'));
});
