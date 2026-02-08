<?php

use App\Models\Document;
use App\Models\User;

describe('Document Model', function () {
    it('belongs to a user', function () {
        $user = User::factory()->create();
        $document = Document::factory()->for($user)->create();

        expect($document->user)->toBeInstanceOf(User::class);
        expect($document->user->id)->toBe($user->id);
    });

    it('casts tags to array', function () {
        $document = Document::factory()->create([
            'tags' => ['invoice', 'contract'],
        ]);

        expect($document->tags)->toBeArray();
        expect($document->tags)->toBe(['invoice', 'contract']);
    });

    it('casts ai_analyzed to boolean', function () {
        $document = Document::factory()->create(['ai_analyzed' => 1]);

        expect($document->ai_analyzed)->toBeBool();
        expect($document->ai_analyzed)->toBeTrue();
    });

    it('can determine if it is public', function () {
        $publicDocument = Document::factory()->public()->create();
        $privateDocument = Document::factory()->create();

        expect($publicDocument->isPublic())->toBeTrue();
        expect($privateDocument->isPublic())->toBeFalse();
    });

    it('can determine if it is private', function () {
        $publicDocument = Document::factory()->public()->create();
        $privateDocument = Document::factory()->create();

        expect($publicDocument->isPrivate())->toBeFalse();
        expect($privateDocument->isPrivate())->toBeTrue();
    });

    it('can make a document public', function () {
        $document = Document::factory()->create();

        expect($document->isPrivate())->toBeTrue();

        $document->makePublic();
        $document->refresh();

        expect($document->isPublic())->toBeTrue();
        expect($document->public_token)->not->toBeNull();
        expect($document->public_enabled_at)->not->toBeNull();
    });

    it('can make a document private', function () {
        $document = Document::factory()->public()->create();

        expect($document->isPublic())->toBeTrue();

        $document->makePrivate();
        $document->refresh();

        expect($document->isPrivate())->toBeTrue();
        expect($document->public_disabled_at)->not->toBeNull();
    });

    it('generates a high-entropy public token', function () {
        $document = Document::factory()->create();
        $token = $document->generatePublicToken();

        expect($token)->toBeString();
        expect(strlen($token))->toBe(64);
    });

    it('returns public URL only when public', function () {
        $publicDocument = Document::factory()->public()->create();
        $privateDocument = Document::factory()->create();

        expect($publicDocument->getPublicUrl())->not->toBeNull();
        expect($publicDocument->getPublicUrl())->toContain('/p/');
        expect($privateDocument->getPublicUrl())->toBeNull();
    });

    it('formats file size correctly', function () {
        $kb = Document::factory()->create(['size_bytes' => 1024]);
        $mb = Document::factory()->create(['size_bytes' => 1024 * 1024]);

        expect($kb->formatted_size)->toBe('1 KB');
        expect($mb->formatted_size)->toBe('1 MB');
    });

    it('extracts file extension from original name', function () {
        $pdf = Document::factory()->create(['original_name' => 'document.pdf']);
        $jpg = Document::factory()->create(['original_name' => 'photo.jpg']);

        expect($pdf->extension)->toBe('pdf');
        expect($jpg->extension)->toBe('jpg');
    });

    it('can determine sensitivity', function () {
        $safe = Document::factory()->create(['sensitivity' => 'safe']);
        $maybe = Document::factory()->create(['sensitivity' => 'maybe_sensitive']);
        $sensitive = Document::factory()->create(['sensitivity' => 'sensitive']);

        expect($safe->isSensitive())->toBeFalse();
        expect($safe->isMaybeSensitive())->toBeFalse();

        expect($maybe->isSensitive())->toBeFalse();
        expect($maybe->isMaybeSensitive())->toBeTrue();

        expect($sensitive->isSensitive())->toBeTrue();
        expect($sensitive->isMaybeSensitive())->toBeFalse();
    });

    it('can filter by visibility scope', function () {
        $user = User::factory()->create();
        Document::factory()->for($user)->count(3)->create(['visibility' => 'private']);
        Document::factory()->for($user)->count(2)->public()->create();

        $privateCount = Document::visibility('private')->count();
        $publicCount = Document::visibility('public')->count();

        expect($privateCount)->toBe(3);
        expect($publicCount)->toBe(2);
    });

    it('can search documents', function () {
        $user = User::factory()->create();
        Document::factory()->for($user)->create([
            'title' => 'Invoice for Project Alpha',
            'original_name' => 'random.pdf',
            'tags' => null,
        ]);
        Document::factory()->for($user)->create([
            'title' => 'Contract Agreement',
            'original_name' => 'agreement.pdf',
            'tags' => null,
        ]);
        Document::factory()->for($user)->create([
            'title' => 'Report',
            'description' => 'Monthly report for Project Alpha',
            'tags' => null,
        ]);

        expect(Document::search('Alpha')->get())->toHaveCount(2);
        expect(Document::search('Contract')->get())->toHaveCount(1);
        expect(Document::search('nonexistent')->get())->toHaveCount(0);
    });
});
