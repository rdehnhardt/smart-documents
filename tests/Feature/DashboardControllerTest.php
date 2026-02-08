<?php

use App\Models\Document;
use App\Models\User;

describe('Dashboard Controller', function () {
    beforeEach(function () {
        $this->withoutVite();
    });

    it('displays the dashboard for authenticated users', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertSuccessful();
        $response->assertInertia(
            fn ($page) => $page
                ->component('dashboard')
                ->has('stats')
                ->has('recentDocuments')
                ->has('documentsByType')
        );
    });

    it('redirects guests to login', function () {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    });

    it('returns correct stats', function () {
        $user = User::factory()->create();

        Document::factory()->for($user)->count(3)->create(['visibility' => 'private', 'ai_analyzed' => true]);
        Document::factory()->for($user)->count(2)->public()->create(['ai_analyzed' => true]);
        Document::factory()->for($user)->create(['ai_analyzed' => false]);
        Document::factory()->for($user)->sensitive()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertInertia(
            fn ($page) => $page
                ->where('stats.total_documents', 7)
                ->where('stats.public_documents', 2)
                ->where('stats.private_documents', 5)
                ->where('stats.pending_analysis', 1)
                ->where('stats.sensitive_documents', 1)
        );
    });

    it('returns only recent documents for the user', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Document::factory()->for($user)->count(3)->create();
        Document::factory()->for($otherUser)->count(5)->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertInertia(
            fn ($page) => $page
                ->has('recentDocuments', 3)
                ->where('stats.total_documents', 3)
        );
    });

    it('limits recent documents to 5', function () {
        $user = User::factory()->create();

        Document::factory()->for($user)->count(10)->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertInertia(
            fn ($page) => $page->has('recentDocuments', 5)
        );
    });
});
