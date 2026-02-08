<?php

use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

describe('Document Controller', function () {
    beforeEach(function () {
        Storage::fake('documents');
        $this->withoutVite();
    });

    describe('index', function () {
        it('displays the documents index page for authenticated users', function () {
            $user = User::factory()->create();
            Document::factory()->for($user)->count(3)->create();

            $response = $this->actingAs($user)->get(route('documents.index'));

            $response->assertSuccessful();
            $response->assertInertia(
                fn ($page) => $page
                    ->component('documents/index')
                    ->has('documents.data', 3)
            );
        });

        it('only shows documents owned by the user', function () {
            $user = User::factory()->create();
            $otherUser = User::factory()->create();

            Document::factory()->for($user)->count(2)->create();
            Document::factory()->for($otherUser)->count(3)->create();

            $response = $this->actingAs($user)->get(route('documents.index'));

            $response->assertInertia(
                fn ($page) => $page->has('documents.data', 2)
            );
        });

        it('can filter by visibility', function () {
            $user = User::factory()->create();
            Document::factory()->for($user)->count(3)->create(['visibility' => 'private']);
            Document::factory()->for($user)->count(2)->public()->create();

            $response = $this->actingAs($user)->get(route('documents.index', [
                'visibility' => 'public',
            ]));

            $response->assertInertia(
                fn ($page) => $page->has('documents.data', 2)
            );
        });

        it('can search documents', function () {
            $user = User::factory()->create();
            Document::factory()->for($user)->create(['title' => 'Invoice 2024', 'tags' => null]);
            Document::factory()->for($user)->create(['title' => 'Contract', 'tags' => null]);
            Document::factory()->for($user)->create(['title' => 'Report', 'tags' => null]);

            $response = $this->actingAs($user)->get(route('documents.index', [
                'search' => 'Invoice',
            ]));

            $response->assertInertia(
                fn ($page) => $page->has('documents.data', 1)
            );
        });

        it('redirects guests to login', function () {
            $response = $this->get(route('documents.index'));
            $response->assertRedirect(route('login'));
        });
    });

    describe('create', function () {
        it('displays the create page for authenticated users', function () {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->get(route('documents.create'));

            $response->assertSuccessful();
            $response->assertInertia(
                fn ($page) => $page
                    ->component('documents/create')
                    ->has('maxFileSize')
            );
        });
    });

    describe('show', function () {
        it('displays document details for the owner', function () {
            $user = User::factory()->create();
            $document = Document::factory()->for($user)->create();
            Storage::disk('documents')->put($document->storage_path, 'test content');

            $response = $this->actingAs($user)->get(route('documents.show', $document));

            $response->assertSuccessful();
            $response->assertInertia(
                fn ($page) => $page
                    ->component('documents/show')
                    ->where('document.id', $document->id)
            );
        });

        it('returns forbidden for non-owners', function () {
            $owner = User::factory()->create();
            $otherUser = User::factory()->create();
            $document = Document::factory()->for($owner)->create();

            $response = $this->actingAs($otherUser)->get(route('documents.show', $document));

            $response->assertForbidden();
        });

        it('includes QR code for public documents', function () {
            $user = User::factory()->create();
            $document = Document::factory()->for($user)->public()->create();
            Storage::disk('documents')->put($document->storage_path, 'test content');

            $response = $this->actingAs($user)->get(route('documents.show', $document));

            $response->assertInertia(
                fn ($page) => $page
                    ->where('qrCode', fn ($qr) => str_contains($qr, 'data:image/svg+xml'))
                    ->has('publicUrl')
            );
        });
    });

    describe('edit', function () {
        it('displays the edit page for the owner', function () {
            $user = User::factory()->create();
            $document = Document::factory()->for($user)->create();

            $response = $this->actingAs($user)->get(route('documents.edit', $document));

            $response->assertSuccessful();
            $response->assertInertia(
                fn ($page) => $page
                    ->component('documents/edit')
                    ->where('document.id', $document->id)
            );
        });

        it('returns forbidden for non-owners', function () {
            $owner = User::factory()->create();
            $otherUser = User::factory()->create();
            $document = Document::factory()->for($owner)->create();

            $response = $this->actingAs($otherUser)->get(route('documents.edit', $document));

            $response->assertForbidden();
        });
    });

    describe('update', function () {
        it('updates document metadata', function () {
            $user = User::factory()->create();
            $document = Document::factory()->for($user)->create();

            $response = $this->actingAs($user)
                ->put(route('documents.update', $document), [
                    'title' => 'Updated Title',
                    'description' => 'Updated description',
                    'tags' => ['new', 'tags'],
                ]);

            $response->assertRedirect(route('documents.show', $document));
            $document->refresh();

            expect($document->title)->toBe('Updated Title');
            expect($document->description)->toBe('Updated description');
            expect($document->tags)->toBe(['new', 'tags']);
        });

        it('validates title length', function () {
            $user = User::factory()->create();
            $document = Document::factory()->for($user)->create();

            $response = $this->actingAs($user)
                ->put(route('documents.update', $document), [
                    'title' => str_repeat('a', 256),
                ]);

            $response->assertSessionHasErrors('title');
        });
    });

    describe('destroy', function () {
        it('deletes the document and file', function () {
            $user = User::factory()->create();
            $document = Document::factory()->for($user)->create();
            Storage::disk('documents')->put($document->storage_path, 'test content');

            $response = $this->actingAs($user)
                ->delete(route('documents.destroy', $document));

            $response->assertRedirect(route('documents.index'));
            expect(Document::find($document->id))->toBeNull();
            Storage::disk('documents')->assertMissing($document->storage_path);
        });

        it('returns forbidden for non-owners', function () {
            $owner = User::factory()->create();
            $otherUser = User::factory()->create();
            $document = Document::factory()->for($owner)->create();

            $response = $this->actingAs($otherUser)
                ->delete(route('documents.destroy', $document));

            $response->assertForbidden();
            expect(Document::find($document->id))->not->toBeNull();
        });
    });

    describe('visibility', function () {
        it('can make a document public', function () {
            $user = User::factory()->create();
            $document = Document::factory()->for($user)->create([
                'sensitivity' => 'safe',
            ]);

            $response = $this->actingAs($user)
                ->post(route('documents.make-public', $document));

            $response->assertRedirect();
            $document->refresh();

            expect($document->visibility)->toBe('public');
            expect($document->public_token)->not->toBeNull();
        });

        it('can make a document private', function () {
            $user = User::factory()->create();
            $document = Document::factory()->for($user)->public()->create();

            $response = $this->actingAs($user)
                ->post(route('documents.make-private', $document));

            $response->assertRedirect();
            $document->refresh();

            expect($document->visibility)->toBe('private');
        });

        it('cannot make a sensitive document public', function () {
            $user = User::factory()->create();
            $document = Document::factory()->for($user)->create([
                'sensitivity' => 'sensitive',
            ]);

            $response = $this->actingAs($user)
                ->post(route('documents.make-public', $document));

            $response->assertForbidden();
            $document->refresh();

            expect($document->visibility)->toBe('private');
        });
    });

    describe('download', function () {
        it('allows owner to download the document', function () {
            $user = User::factory()->create();
            $document = Document::factory()->for($user)->create([
                'original_name' => 'test.pdf',
                'mime_type' => 'application/pdf',
            ]);
            Storage::disk('documents')->put($document->storage_path, 'test content');

            $response = $this->actingAs($user)
                ->get(route('documents.download', $document));

            $response->assertSuccessful();
            $response->assertHeader('Content-Type', 'application/pdf');
        });

        it('returns forbidden for non-owners', function () {
            $owner = User::factory()->create();
            $otherUser = User::factory()->create();
            $document = Document::factory()->for($owner)->create();
            Storage::disk('documents')->put($document->storage_path, 'test content');

            $response = $this->actingAs($otherUser)
                ->get(route('documents.download', $document));

            $response->assertForbidden();
        });
    });
});
