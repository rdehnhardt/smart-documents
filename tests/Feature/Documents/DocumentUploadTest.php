<?php

use App\Jobs\AnalyzeDocumentJob;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

describe('Document Upload', function () {
    beforeEach(function () {
        Storage::fake('documents');
        Queue::fake();
    });

    it('allows authenticated users to upload a PDF', function () {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($user)
            ->post(route('documents.store'), [
                'file' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        expect(Document::count())->toBe(1);
        expect(Document::first()->user_id)->toBe($user->id);
        expect(Document::first()->mime_type)->toBe('application/pdf');
    });

    it('allows authenticated users to upload an image', function () {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);

        $response = $this->actingAs($user)
            ->post(route('documents.store'), [
                'file' => $file,
            ]);

        $response->assertRedirect();
        expect(Document::count())->toBe(1);
    });

    it('allows authenticated users to upload a docx', function () {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create(
            'document.docx',
            1024,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        );

        $response = $this->actingAs($user)
            ->post(route('documents.store'), [
                'file' => $file,
            ]);

        $response->assertRedirect();
        expect(Document::count())->toBe(1);
    });

    it('allows authenticated users to upload a xlsx', function () {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create(
            'spreadsheet.xlsx',
            1024,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

        $response = $this->actingAs($user)
            ->post(route('documents.store'), [
                'file' => $file,
            ]);

        $response->assertRedirect();
        expect(Document::count())->toBe(1);
    });

    it('allows authenticated users to upload a txt file', function () {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('notes.txt', 100, 'text/plain');

        $response = $this->actingAs($user)
            ->post(route('documents.store'), [
                'file' => $file,
            ]);

        $response->assertRedirect();
        expect(Document::count())->toBe(1);
    });

    it('stores file with title and description', function () {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($user)
            ->post(route('documents.store'), [
                'file' => $file,
                'title' => 'My Important Document',
                'description' => 'This is a test document',
            ]);

        $response->assertRedirect();
        $document = Document::first();
        expect($document->title)->toBe('My Important Document');
        expect($document->description)->toBe('This is a test document');
    });

    it('dispatches AI analysis job after upload', function () {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $this->actingAs($user)
            ->post(route('documents.store'), [
                'file' => $file,
            ]);

        Queue::assertPushed(AnalyzeDocumentJob::class, function ($job) {
            return $job->document instanceof Document;
        });
    });

    it('rejects files larger than 100MB', function () {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('large.pdf', 101 * 1024, 'application/pdf');

        $response = $this->actingAs($user)
            ->post(route('documents.store'), [
                'file' => $file,
            ]);

        $response->assertSessionHasErrors('file');
        expect(Document::count())->toBe(0);
    });

    it('accepts any file type', function () {
        Queue::fake();
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('script.php', 100, 'application/x-php');

        $response = $this->actingAs($user)
            ->post(route('documents.store'), [
                'file' => $file,
            ]);

        $response->assertRedirect();
        expect(Document::count())->toBe(1);
        expect(Document::first()->original_name)->toBe('script.php');
    });

    it('rejects requests without a file', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('documents.store'), []);

        $response->assertSessionHasErrors('file');
    });

    it('redirects guests to login', function () {
        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $response = $this->post(route('documents.store'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('login'));
    });
});
