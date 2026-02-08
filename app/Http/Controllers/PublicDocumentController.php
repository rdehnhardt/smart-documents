<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\DocumentStorageService;
use App\Services\QrCodeService;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicDocumentController extends Controller
{
    public function __construct(
        protected DocumentStorageService $storageService,
        protected QrCodeService $qrCodeService
    ) {}

    /**
     * Download a publicly shared document.
     */
    public function show(string $token): StreamedResponse
    {
        $document = Document::where('public_token', $token)
            ->where('visibility', 'public')
            ->firstOrFail();

        return $this->storageService->stream($document);
    }

    /**
     * Get the QR code for a publicly shared document.
     */
    public function qrCode(string $token): Response
    {
        $document = Document::where('public_token', $token)
            ->where('visibility', 'public')
            ->firstOrFail();

        $svg = $this->qrCodeService->generateSvg($document);

        if (! $svg) {
            abort(404);
        }

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
