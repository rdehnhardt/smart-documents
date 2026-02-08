<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentStorageService
{
    /**
     * The maximum file size in bytes (100MB).
     */
    public const MAX_FILE_SIZE = 100 * 1024 * 1024;

    /**
     * The storage disk for documents.
     */
    protected string $disk = 'documents';

    /**
     * Store an uploaded document.
     */
    public function store(UploadedFile $file, User $user, ?string $title = null, ?string $description = null): Document
    {
        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $extension = $file->getClientOriginalExtension() ?: 'bin';
        $size = $file->getSize();

        $storagePath = $this->generateStoragePath($user, $extension);

        Storage::disk($this->disk)->put($storagePath, $file->getContent());

        return Document::create([
            'user_id' => $user->id,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'size_bytes' => $size,
            'storage_disk' => $this->disk,
            'storage_path' => $storagePath,
            'title' => $title,
            'description' => $description,
        ]);
    }

    /**
     * Generate a unique storage path for the document.
     */
    protected function generateStoragePath(User $user, string $extension): string
    {
        $date = now()->format('Y/m/d');
        $uuid = Str::uuid();

        return "documents/{$user->id}/{$date}/{$uuid}.{$extension}";
    }

    /**
     * Get the file contents for a document.
     */
    public function getContents(Document $document): string
    {
        return Storage::disk($document->storage_disk)->get($document->storage_path);
    }

    /**
     * Stream a document for download.
     */
    public function download(Document $document): StreamedResponse
    {
        $disk = Storage::disk($document->storage_disk);

        if (! $disk->exists($document->storage_path)) {
            abort(404, 'Document file not found in storage.');
        }

        return $disk->download($document->storage_path, $document->original_name, [
            'Content-Type' => $document->mime_type,
        ]);
    }

    /**
     * Stream a document for inline viewing.
     */
    public function stream(Document $document): StreamedResponse
    {
        $disk = Storage::disk($document->storage_disk);

        if (! $disk->exists($document->storage_path)) {
            abort(404, 'Document file not found in storage.');
        }

        return response()->streamDownload(function () use ($disk, $document) {
            echo $disk->get($document->storage_path);
        }, $document->original_name, [
            'Content-Type' => $document->mime_type,
            'Content-Length' => $document->size_bytes,
            'Content-Disposition' => 'inline; filename="'.$document->original_name.'"',
        ]);
    }

    /**
     * Delete a document from storage.
     */
    public function delete(Document $document): bool
    {
        $deleted = Storage::disk($document->storage_disk)->delete($document->storage_path);

        if ($deleted) {
            $document->delete();
        }

        return $deleted;
    }

    /**
     * Check if a document exists in storage.
     */
    public function exists(Document $document): bool
    {
        return Storage::disk($document->storage_disk)->exists($document->storage_path);
    }

    /**
     * Get the full path to a document.
     */
    public function getPath(Document $document): string
    {
        return Storage::disk($document->storage_disk)->path($document->storage_path);
    }
}
