<?php

use App\Models\Document;
use App\Models\User;

describe('Document Policy', function () {
    it('allows any authenticated user to view any documents', function () {
        $user = User::factory()->create();

        expect($user->can('viewAny', Document::class))->toBeTrue();
    });

    it('allows only owner to view a document', function () {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $document = Document::factory()->for($owner)->create();

        expect($owner->can('view', $document))->toBeTrue();
        expect($otherUser->can('view', $document))->toBeFalse();
    });

    it('allows any authenticated user to create documents', function () {
        $user = User::factory()->create();

        expect($user->can('create', Document::class))->toBeTrue();
    });

    it('allows only owner to update a document', function () {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $document = Document::factory()->for($owner)->create();

        expect($owner->can('update', $document))->toBeTrue();
        expect($otherUser->can('update', $document))->toBeFalse();
    });

    it('allows only owner to delete a document', function () {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $document = Document::factory()->for($owner)->create();

        expect($owner->can('delete', $document))->toBeTrue();
        expect($otherUser->can('delete', $document))->toBeFalse();
    });

    it('allows only owner to download a document', function () {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $document = Document::factory()->for($owner)->create();

        expect($owner->can('download', $document))->toBeTrue();
        expect($otherUser->can('download', $document))->toBeFalse();
    });

    it('allows owner to change visibility of non-sensitive document', function () {
        $owner = User::factory()->create();
        $document = Document::factory()->for($owner)->create([
            'sensitivity' => 'safe',
        ]);

        expect($owner->can('changeVisibility', $document))->toBeTrue();
    });

    it('denies changing visibility of sensitive document', function () {
        $owner = User::factory()->create();
        $document = Document::factory()->for($owner)->create([
            'sensitivity' => 'sensitive',
        ]);

        expect($owner->can('changeVisibility', $document))->toBeFalse();
    });

    it('denies non-owner from changing visibility', function () {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $document = Document::factory()->for($owner)->create([
            'sensitivity' => 'safe',
        ]);

        expect($otherUser->can('changeVisibility', $document))->toBeFalse();
    });

    it('allows only owner to force delete a document', function () {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $document = Document::factory()->for($owner)->create();

        expect($owner->can('forceDelete', $document))->toBeTrue();
        expect($otherUser->can('forceDelete', $document))->toBeFalse();
    });
});
