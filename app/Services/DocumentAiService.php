<?php

namespace App\Services;

use App\Ai\Agents\DocumentAnalyzer;
use App\Models\Document;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Files\Document as AiDocument;
use Laravel\Ai\Files\Image as AiImage;
use Throwable;

class DocumentAiService
{
    /**
     * Supported file types for AI document attachments.
     */
    protected const SUPPORTED_ATTACHMENT_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    /**
     * Analyze a document and update its metadata.
     *
     * @return array{title: string, description: string, tags: array<string>, summary: string, sensitivity: string}|null
     */
    public function analyze(Document $document): ?array
    {
        try {
            // For text-based files, read content and analyze via prompt
            if ($this->isTextFile($document)) {
                return $this->analyzeTextFile($document);
            }

            $attachments = $this->getAttachments($document);

            if (empty($attachments)) {
                return $this->analyzeFromMetadata($document);
            }

            $prompt = $this->buildPrompt($document);

            $response = DocumentAnalyzer::make()->prompt($prompt, attachments: $attachments);

            Log::info('AI analysis response received', [
                'document_id' => $document->id,
                'response_type' => get_class($response),
            ]);

            return [
                'title' => $response['title'] ?? null,
                'description' => $response['description'] ?? null,
                'tags' => $response['tags'] ?? [],
                'summary' => $response['summary'] ?? null,
                'sensitivity' => $response['sensitivity'] ?? 'safe',
            ];
        } catch (Throwable $e) {
            Log::warning('Document AI analysis failed', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Analyze a text-based file by reading its content.
     *
     * @return array{title: string, description: string, tags: array<string>, summary: string, sensitivity: string}|null
     */
    protected function analyzeTextFile(Document $document): ?array
    {
        $content = Storage::disk($document->storage_disk)->get($document->storage_path);

        if (empty($content)) {
            return $this->analyzeFromMetadata($document);
        }

        // Truncate content to avoid token limits (roughly 10k characters)
        $truncatedContent = mb_substr($content, 0, 10000);
        if (mb_strlen($content) > 10000) {
            $truncatedContent .= "\n\n[Content truncated...]";
        }

        $prompt = $this->buildTextPrompt($document, $truncatedContent);

        $response = DocumentAnalyzer::make()->prompt($prompt);

        Log::info('AI text analysis response received', [
            'document_id' => $document->id,
        ]);

        return [
            'title' => $response['title'] ?? null,
            'description' => $response['description'] ?? null,
            'tags' => $response['tags'] ?? [],
            'summary' => $response['summary'] ?? null,
            'sensitivity' => $response['sensitivity'] ?? 'safe',
        ];
    }

    /**
     * Determine if the document is a text-based file.
     */
    protected function isTextFile(Document $document): bool
    {
        $textMimeTypes = [
            'text/plain',
            'text/markdown',
            'text/html',
            'text/css',
            'text/csv',
            'application/json',
            'application/xml',
            'text/xml',
        ];

        $textExtensions = ['md', 'txt', 'json', 'xml', 'csv', 'html', 'css', 'js', 'ts', 'php', 'py', 'rb', 'yml', 'yaml'];

        $extension = strtolower(pathinfo($document->original_name, PATHINFO_EXTENSION));

        return in_array($document->mime_type, $textMimeTypes) || in_array($extension, $textExtensions);
    }

    /**
     * Build the prompt for text file analysis.
     */
    protected function buildTextPrompt(Document $document, string $content): string
    {
        return <<<PROMPT
Analyze the following document content. Here is some context:

- Original filename: {$document->original_name}
- File type: {$document->mime_type}
- File size: {$document->formatted_size}

Document content:
---
{$content}
---

Please provide:
1. A suggested title
2. A short description
3. Relevant tags (up to 5)
4. A brief summary
5. A sensitivity classification (safe, maybe_sensitive, or sensitive)
PROMPT;
    }

    /**
     * Get the attachments for AI analysis based on document type.
     *
     * @return array<\Laravel\Ai\Files\Document|\Laravel\Ai\Files\Image>
     */
    protected function getAttachments(Document $document): array
    {
        $path = Storage::disk($document->storage_disk)->path($document->storage_path);

        if (! file_exists($path)) {
            return [];
        }

        if ($this->isImage($document)) {
            return [AiImage::fromPath($path)];
        }

        return [AiDocument::fromPath($path)];
    }

    /**
     * Analyze document from metadata when file content is unavailable.
     *
     * @return array{title: string, description: string, tags: array<string>, summary: string, sensitivity: string}
     */
    protected function analyzeFromMetadata(Document $document): array
    {
        $extension = pathinfo($document->original_name, PATHINFO_EXTENSION);
        $baseName = pathinfo($document->original_name, PATHINFO_FILENAME);

        return [
            'title' => $document->title ?? ucwords(str_replace(['-', '_'], ' ', $baseName)),
            'description' => $document->description ?? "A {$extension} document.",
            'tags' => [$extension],
            'summary' => "This is a {$document->mime_type} file named {$document->original_name}.",
            'sensitivity' => 'safe',
        ];
    }

    /**
     * Build the prompt for document analysis.
     */
    protected function buildPrompt(Document $document): string
    {
        return <<<PROMPT
Analyze the attached document. Here is some context:

- Original filename: {$document->original_name}
- File type: {$document->mime_type}
- File size: {$document->formatted_size}

Please provide:
1. A suggested title
2. A short description
3. Relevant tags (up to 5)
4. A brief summary
5. A sensitivity classification (safe, maybe_sensitive, or sensitive)
PROMPT;
    }

    /**
     * Determine if the document is an image.
     */
    protected function isImage(Document $document): bool
    {
        return str_starts_with($document->mime_type, 'image/');
    }

    /**
     * Apply AI analysis results to a document.
     */
    public function applyAnalysis(Document $document, array $analysis, bool $forceUpdate = false): void
    {
        $updates = [
            'ai_analyzed' => true,
            'sensitivity' => $analysis['sensitivity'] ?? 'safe',
        ];

        if (! empty($analysis['summary'])) {
            $updates['ai_summary'] = $analysis['summary'];
        }

        if (! empty($analysis['title']) && ($forceUpdate || empty($document->title))) {
            $updates['title'] = $analysis['title'];
        }

        if (! empty($analysis['description']) && ($forceUpdate || empty($document->description))) {
            $updates['description'] = $analysis['description'];
        }

        if (! empty($analysis['tags']) && ($forceUpdate || empty($document->tags))) {
            $updates['tags'] = $analysis['tags'];
        }

        if ($analysis['sensitivity'] === 'sensitive' && $document->isPublic()) {
            $updates['visibility'] = 'private';
            $updates['public_disabled_at'] = now();
        }

        $document->update($updates);
    }
}
