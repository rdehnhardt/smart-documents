<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\DocumentAiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class AnalyzeDocumentJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Document $document,
        public bool $forceUpdate = false
    ) {}

    /**
     * Execute the job.
     */
    public function handle(DocumentAiService $aiService): void
    {
        if ($this->document->ai_analyzed && ! $this->forceUpdate) {
            return;
        }

        Log::info('Starting AI analysis for document', [
            'document_id' => $this->document->id,
            'original_name' => $this->document->original_name,
            'force_update' => $this->forceUpdate,
        ]);

        $analysis = $aiService->analyze($this->document);

        if ($analysis === null) {
            Log::warning('AI analysis returned no results', [
                'document_id' => $this->document->id,
            ]);

            $this->document->update(['ai_analyzed' => true]);

            return;
        }

        $aiService->applyAnalysis($this->document, $analysis, $this->forceUpdate);

        Log::info('AI analysis completed for document', [
            'document_id' => $this->document->id,
            'sensitivity' => $analysis['sensitivity'],
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error('Document AI analysis job failed', [
            'document_id' => $this->document->id,
            'error' => $exception?->getMessage(),
        ]);

        $this->document->update(['ai_analyzed' => true]);
    }
}
