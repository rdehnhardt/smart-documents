<?php

use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

describe('Public Document Access', function () {
    beforeEach(function () {
        Storage::fake('documents');
    });

    describe('viewing public documents', function () {
        it('allows anyone to view a public document', function () {
            $document = Document::factory()->public()->create([
                'original_name' => 'public-file.pdf',
                'mime_type' => 'application/pdf',
            ]);
            Storage::disk('documents')->put($document->storage_path, 'test content');

            $response = $this->get(route('public.document', $document->public_token));

            $response->assertSuccessful();
            $response->assertHeader('Content-Type', 'application/pdf');
        });

        it('returns 404 for invalid token', function () {
            $response = $this->get(route('public.document', 'invalid-token'));

            $response->assertNotFound();
        });

        it('returns 404 for private documents', function () {
            $document = Document::factory()->create([
                'public_token' => 'valid-but-private-token',
            ]);
            Storage::disk('documents')->put($document->storage_path, 'test content');

            $response = $this->get(route('public.document', $document->public_token));

            $response->assertNotFound();
        });

        it('streams image files correctly', function () {
            $document = Document::factory()->public()->create([
                'original_name' => 'photo.jpg',
                'mime_type' => 'image/jpeg',
            ]);
            Storage::disk('documents')->put($document->storage_path, 'fake image content');

            $response = $this->get(route('public.document', $document->public_token));

            $response->assertSuccessful();
            $response->assertHeader('Content-Type', 'image/jpeg');
        });
    });

    describe('QR code generation', function () {
        it('generates a QR code for public documents', function () {
            $document = Document::factory()->public()->create();
            Storage::disk('documents')->put($document->storage_path, 'test content');

            $response = $this->get(route('public.document.qr', $document->public_token));

            $response->assertSuccessful();
            $response->assertHeader('Content-Type', 'image/svg+xml');
        });

        it('returns 404 for QR code of private documents', function () {
            $document = Document::factory()->create([
                'public_token' => 'private-token',
            ]);

            $response = $this->get(route('public.document.qr', $document->public_token));

            $response->assertNotFound();
        });

        it('returns 404 for QR code with invalid token', function () {
            $response = $this->get(route('public.document.qr', 'nonexistent'));

            $response->assertNotFound();
        });
    });

    describe('public URL format', function () {
        it('uses the correct public URL format', function () {
            $document = Document::factory()->public()->create();

            $url = $document->getPublicUrl();

            expect($url)->toContain('/p/');
            expect($url)->toContain($document->public_token);
        });

        it('returns null for private documents', function () {
            $document = Document::factory()->create();

            expect($document->getPublicUrl())->toBeNull();
        });
    });
});
