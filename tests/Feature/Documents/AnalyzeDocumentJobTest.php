<?php

use App\Jobs\AnalyzeDocumentJob;
use App\Models\Document;
use App\Services\DocumentAiService;
use Illuminate\Support\Facades\Storage;

describe('Analyze Document Job', function () {
    beforeEach(function () {
        Storage::fake('documents');
    });

    it('skips analysis if document is already analyzed', function () {
        $document = Document::factory()->create(['ai_analyzed' => true]);

        $job = new AnalyzeDocumentJob($document);
        $aiService = $this->mock(DocumentAiService::class);
        $aiService->shouldNotReceive('analyze');

        $job->handle($aiService);
    });

    it('updates document with AI analysis results', function () {
        $document = Document::factory()->create([
            'ai_analyzed' => false,
            'title' => null,
            'description' => null,
            'tags' => null,
            'ai_summary' => null,
            'sensitivity' => null,
        ]);

        Storage::disk('documents')->put($document->storage_path, 'test content');

        $aiService = $this->mock(DocumentAiService::class);
        $aiService->shouldReceive('analyze')
            ->once()
            ->andReturn([
                'title' => 'AI Generated Title',
                'description' => 'AI Generated Description',
                'tags' => ['invoice', 'financial'],
                'summary' => 'This is an AI generated summary.',
                'sensitivity' => 'safe',
            ]);
        $aiService->shouldReceive('applyAnalysis')
            ->once()
            ->andReturnUsing(function ($doc, $analysis) {
                $doc->update([
                    'ai_analyzed' => true,
                    'title' => $analysis['title'],
                    'description' => $analysis['description'],
                    'tags' => $analysis['tags'],
                    'ai_summary' => $analysis['summary'],
                    'sensitivity' => $analysis['sensitivity'],
                ]);
            });

        $job = new AnalyzeDocumentJob($document);
        $job->handle($aiService);

        $document->refresh();

        expect($document->ai_analyzed)->toBeTrue();
        expect($document->title)->toBe('AI Generated Title');
        expect($document->description)->toBe('AI Generated Description');
        expect($document->tags)->toBe(['invoice', 'financial']);
        expect($document->ai_summary)->toBe('This is an AI generated summary.');
        expect($document->sensitivity)->toBe('safe');
    });

    it('marks document as analyzed even if analysis fails', function () {
        $document = Document::factory()->create([
            'ai_analyzed' => false,
        ]);

        $aiService = $this->mock(DocumentAiService::class);
        $aiService->shouldReceive('analyze')
            ->once()
            ->andReturn(null);

        $job = new AnalyzeDocumentJob($document);
        $job->handle($aiService);

        $document->refresh();

        expect($document->ai_analyzed)->toBeTrue();
    });

    it('makes sensitive documents private via apply analysis', function () {
        $document = Document::factory()->public()->create([
            'ai_analyzed' => false,
        ]);

        Storage::disk('documents')->put($document->storage_path, 'test content');

        $aiService = $this->mock(DocumentAiService::class);
        $aiService->shouldReceive('analyze')
            ->once()
            ->andReturn([
                'title' => 'Sensitive Document',
                'description' => 'Contains sensitive data',
                'tags' => ['sensitive'],
                'summary' => 'This document contains sensitive information.',
                'sensitivity' => 'sensitive',
            ]);
        $aiService->shouldReceive('applyAnalysis')
            ->once()
            ->andReturnUsing(function ($doc, $analysis) {
                $doc->update([
                    'ai_analyzed' => true,
                    'sensitivity' => $analysis['sensitivity'],
                    'visibility' => 'private',
                ]);
            });

        $job = new AnalyzeDocumentJob($document);
        $job->handle($aiService);

        $document->refresh();

        expect($document->sensitivity)->toBe('sensitive');
        expect($document->visibility)->toBe('private');
    });

    it('does not overwrite existing title via apply analysis', function () {
        $document = Document::factory()->create([
            'ai_analyzed' => false,
            'title' => 'User Provided Title',
        ]);

        Storage::disk('documents')->put($document->storage_path, 'test content');

        $aiService = $this->mock(DocumentAiService::class);
        $aiService->shouldReceive('analyze')
            ->once()
            ->andReturn([
                'title' => 'AI Generated Title',
                'description' => 'AI Description',
                'tags' => ['tag'],
                'summary' => 'Summary',
                'sensitivity' => 'safe',
            ]);
        $aiService->shouldReceive('applyAnalysis')
            ->once()
            ->andReturnUsing(function ($doc, $analysis) {
                $doc->update([
                    'ai_analyzed' => true,
                    'ai_summary' => $analysis['summary'],
                    'sensitivity' => $analysis['sensitivity'],
                ]);
            });

        $job = new AnalyzeDocumentJob($document);
        $job->handle($aiService);

        $document->refresh();

        expect($document->title)->toBe('User Provided Title');
    });

    it('handles job failure gracefully', function () {
        $document = Document::factory()->create([
            'ai_analyzed' => false,
        ]);

        $job = new AnalyzeDocumentJob($document);
        $job->failed(new Exception('Test exception'));

        $document->refresh();

        expect($document->ai_analyzed)->toBeTrue();
    });
});
