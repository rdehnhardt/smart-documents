<?php

use App\Models\Document;
use App\Models\User;
use App\Services\DocumentStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

describe('Document Storage Service', function () {
    beforeEach(function () {
        Storage::fake('documents');
    });

    describe('store', function () {
        it('stores a file and creates a document record', function () {
            $user = User::factory()->create();
            $file = UploadedFile::fake()->create('test.pdf', 1024, 'application/pdf');
            $service = app(DocumentStorageService::class);

            $document = $service->store($file, $user);

            expect($document)->toBeInstanceOf(Document::class);
            expect($document->user_id)->toBe($user->id);
            expect($document->original_name)->toBe('test.pdf');
            expect($document->mime_type)->toBe('application/pdf');
            Storage::disk('documents')->assertExists($document->storage_path);
        });

        it('stores file with provided title and description', function () {
            $user = User::factory()->create();
            $file = UploadedFile::fake()->create('test.pdf', 1024, 'application/pdf');
            $service = app(DocumentStorageService::class);

            $document = $service->store($file, $user, 'My Title', 'My Description');

            expect($document->title)->toBe('My Title');
            expect($document->description)->toBe('My Description');
        });

        it('generates unique storage paths', function () {
            $user = User::factory()->create();
            $file1 = UploadedFile::fake()->create('test1.pdf', 1024, 'application/pdf');
            $file2 = UploadedFile::fake()->create('test2.pdf', 1024, 'application/pdf');
            $service = app(DocumentStorageService::class);

            $document1 = $service->store($file1, $user);
            $document2 = $service->store($file2, $user);

            expect($document1->storage_path)->not->toBe($document2->storage_path);
        });
    });

    describe('getContents', function () {
        it('returns the file contents', function () {
            $user = User::factory()->create();
            $document = Document::factory()->for($user)->create();
            Storage::disk('documents')->put($document->storage_path, 'test file contents');
            $service = app(DocumentStorageService::class);

            $contents = $service->getContents($document);

            expect($contents)->toBe('test file contents');
        });
    });

    describe('delete', function () {
        it('deletes the file and document record', function () {
            $user = User::factory()->create();
            $document = Document::factory()->for($user)->create();
            Storage::disk('documents')->put($document->storage_path, 'test content');
            $service = app(DocumentStorageService::class);

            $result = $service->delete($document);

            expect($result)->toBeTrue();
            Storage::disk('documents')->assertMissing($document->storage_path);
            expect(Document::find($document->id))->toBeNull();
        });
    });

    describe('exists', function () {
        it('returns true if file exists', function () {
            $document = Document::factory()->create();
            Storage::disk('documents')->put($document->storage_path, 'content');
            $service = app(DocumentStorageService::class);

            expect($service->exists($document))->toBeTrue();
        });

        it('returns false if file does not exist', function () {
            $document = Document::factory()->create();
            $service = app(DocumentStorageService::class);

            expect($service->exists($document))->toBeFalse();
        });
    });

    describe('max file size', function () {
        it('defines max file size as 100MB', function () {
            expect(DocumentStorageService::MAX_FILE_SIZE)->toBe(100 * 1024 * 1024);
        });
    });
});
