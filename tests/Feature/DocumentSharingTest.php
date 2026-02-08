<?php

use App\Models\Document;
use App\Models\User;

describe('Document Sharing', function () {
    describe('Model Relationships', function () {
        it('can share document with another user', function () {
            $owner = User::factory()->create();
            $recipient = User::factory()->create();
            $document = Document::factory()->for($owner)->create();

            $document->sharedWith()->attach($recipient->id, [
                'shared_by' => $owner->id,
                'can_download' => true,
            ]);

            expect($document->sharedWith)->toHaveCount(1);
            expect($document->sharedWith->first()->id)->toBe($recipient->id);
            expect($document->isSharedWith($recipient))->toBeTrue();
        });

        it('user can access shared documents', function () {
            $owner = User::factory()->create();
            $recipient = User::factory()->create();
            $document = Document::factory()->for($owner)->create();

            $document->sharedWith()->attach($recipient->id, [
                'shared_by' => $owner->id,
                'can_download' => true,
            ]);

            expect($recipient->sharedDocuments)->toHaveCount(1);
            expect($recipient->sharedDocuments->first()->id)->toBe($document->id);
        });

        it('checks download permission for shared document', function () {
            $owner = User::factory()->create();
            $recipient = User::factory()->create();
            $document = Document::factory()->for($owner)->create();

            $document->sharedWith()->attach($recipient->id, [
                'shared_by' => $owner->id,
                'can_download' => false,
            ]);

            expect($document->canUserDownload($recipient))->toBeFalse();
            expect($document->canUserDownload($owner))->toBeTrue();
        });
    });

    describe('Policy', function () {
        it('allows shared user to view document', function () {
            $owner = User::factory()->create();
            $recipient = User::factory()->create();
            $document = Document::factory()->for($owner)->create();

            expect($recipient->can('view', $document))->toBeFalse();

            $document->sharedWith()->attach($recipient->id, [
                'shared_by' => $owner->id,
                'can_download' => true,
            ]);

            expect($recipient->can('view', $document))->toBeTrue();
        });

        it('allows shared user to download if permitted', function () {
            $owner = User::factory()->create();
            $recipient = User::factory()->create();
            $document = Document::factory()->for($owner)->create();

            $document->sharedWith()->attach($recipient->id, [
                'shared_by' => $owner->id,
                'can_download' => true,
            ]);

            expect($recipient->can('download', $document))->toBeTrue();
        });

        it('denies shared user download if not permitted', function () {
            $owner = User::factory()->create();
            $recipient = User::factory()->create();
            $document = Document::factory()->for($owner)->create();

            $document->sharedWith()->attach($recipient->id, [
                'shared_by' => $owner->id,
                'can_download' => false,
            ]);

            expect($recipient->can('download', $document))->toBeFalse();
        });

        it('only owner can share document', function () {
            $owner = User::factory()->create();
            $otherUser = User::factory()->create();
            $document = Document::factory()->for($owner)->create();

            expect($owner->can('share', $document))->toBeTrue();
            expect($otherUser->can('share', $document))->toBeFalse();
        });
    });

    describe('Controller', function () {
        it('owner can share document with another user', function () {
            $owner = User::factory()->create();
            $recipient = User::factory()->create();
            $document = Document::factory()->for($owner)->create();

            $this->actingAs($owner)
                ->post(route('documents.shares.store', $document), [
                    'email' => $recipient->email,
                    'can_download' => true,
                ])
                ->assertRedirect();

            expect($document->isSharedWith($recipient))->toBeTrue();
        });

        it('owner cannot share document with themselves', function () {
            $owner = User::factory()->create();
            $document = Document::factory()->for($owner)->create();

            $this->actingAs($owner)
                ->post(route('documents.shares.store', $document), [
                    'email' => $owner->email,
                    'can_download' => true,
                ])
                ->assertSessionHasErrors('email');
        });

        it('cannot share document with non-existent user', function () {
            $owner = User::factory()->create();
            $document = Document::factory()->for($owner)->create();

            $this->actingAs($owner)
                ->post(route('documents.shares.store', $document), [
                    'email' => 'nonexistent@example.com',
                    'can_download' => true,
                ])
                ->assertSessionHasErrors('email');
        });

        it('owner can update share permissions', function () {
            $owner = User::factory()->create();
            $recipient = User::factory()->create();
            $document = Document::factory()->for($owner)->create();

            $document->sharedWith()->attach($recipient->id, [
                'shared_by' => $owner->id,
                'can_download' => true,
            ]);

            $this->actingAs($owner)
                ->patch(route('documents.shares.update', [$document, $recipient]), [
                    'can_download' => false,
                ])
                ->assertRedirect();

            expect($document->canUserDownload($recipient))->toBeFalse();
        });

        it('owner can remove share', function () {
            $owner = User::factory()->create();
            $recipient = User::factory()->create();
            $document = Document::factory()->for($owner)->create();

            $document->sharedWith()->attach($recipient->id, [
                'shared_by' => $owner->id,
                'can_download' => true,
            ]);

            $this->actingAs($owner)
                ->delete(route('documents.shares.destroy', [$document, $recipient]))
                ->assertRedirect();

            expect($document->isSharedWith($recipient))->toBeFalse();
        });

        it('non-owner cannot share document', function () {
            $owner = User::factory()->create();
            $otherUser = User::factory()->create();
            $recipient = User::factory()->create();
            $document = Document::factory()->for($owner)->create();

            $this->actingAs($otherUser)
                ->post(route('documents.shares.store', $document), [
                    'email' => $recipient->email,
                    'can_download' => true,
                ])
                ->assertForbidden();
        });
    });

    describe('Document Index', function () {
        it('shows shared documents count', function () {
            $owner = User::factory()->create();
            $recipient = User::factory()->create();
            $document = Document::factory()->for($owner)->create();

            $document->sharedWith()->attach($recipient->id, [
                'shared_by' => $owner->id,
                'can_download' => true,
            ]);

            $this->actingAs($recipient)
                ->get(route('documents.index'))
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->has('sharedCount')
                    ->where('sharedCount', 1)
                );
        });

        it('shows shared documents in shared filter', function () {
            $owner = User::factory()->create();
            $recipient = User::factory()->create();
            $document = Document::factory()->for($owner)->create();

            $document->sharedWith()->attach($recipient->id, [
                'shared_by' => $owner->id,
                'can_download' => true,
            ]);

            $this->actingAs($recipient)
                ->get(route('documents.index', ['filter' => 'shared']))
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->has('documents.data', 1)
                );
        });
    });

    describe('Document Show', function () {
        it('shared user can view document', function () {
            $owner = User::factory()->create();
            $recipient = User::factory()->create();
            $document = Document::factory()->for($owner)->create();

            $document->sharedWith()->attach($recipient->id, [
                'shared_by' => $owner->id,
                'can_download' => true,
            ]);

            $this->actingAs($recipient)
                ->get(route('documents.show', $document))
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->where('isOwner', false)
                    ->where('canDownload', true)
                );
        });

        it('owner sees shared users list', function () {
            $owner = User::factory()->create();
            $recipient = User::factory()->create();
            $document = Document::factory()->for($owner)->create();

            $document->sharedWith()->attach($recipient->id, [
                'shared_by' => $owner->id,
                'can_download' => true,
            ]);

            $this->actingAs($owner)
                ->get(route('documents.show', $document))
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->where('isOwner', true)
                    ->has('sharedWith', 1)
                );
        });
    });
});
