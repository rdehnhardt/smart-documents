<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $user = Auth::user();

        $documents = Document::where('user_id', $user->id);

        $stats = [
            'total_documents' => $documents->count(),
            'public_documents' => (clone $documents)->where('visibility', 'public')->count(),
            'private_documents' => (clone $documents)->where('visibility', 'private')->count(),
            'total_storage' => $this->formatBytes((clone $documents)->sum('size_bytes')),
            'pending_analysis' => (clone $documents)->where('ai_analyzed', false)->count(),
            'sensitive_documents' => (clone $documents)->where('sensitivity', 'sensitive')->count(),
            'maybe_sensitive_documents' => (clone $documents)->where('sensitivity', 'maybe_sensitive')->count(),
        ];

        $recentDocuments = Document::where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Document $doc) => [
                'id' => $doc->id,
                'title' => $doc->title ?? $doc->original_name,
                'visibility' => $doc->visibility,
                'sensitivity' => $doc->sensitivity,
                'formatted_size' => $doc->formatted_size,
                'created_at' => $doc->created_at->diffForHumans(),
                'ai_analyzed' => $doc->ai_analyzed,
            ]);

        $documentsByType = Document::where('user_id', $user->id)
            ->get()
            ->groupBy(fn (Document $doc) => $this->getFileCategory($doc->mime_type))
            ->map(fn ($group) => $group->count())
            ->toArray();

        return Inertia::render('dashboard', [
            'stats' => $stats,
            'recentDocuments' => $recentDocuments,
            'documentsByType' => $documentsByType,
        ]);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes, 1024));

        return round($bytes / pow(1024, $i), 2).' '.$units[$i];
    }

    private function getFileCategory(string $mimeType): string
    {
        return match (true) {
            str_starts_with($mimeType, 'image/') => 'Images',
            str_starts_with($mimeType, 'video/') => 'Videos',
            str_starts_with($mimeType, 'audio/') => 'Audio',
            str_contains($mimeType, 'pdf') => 'PDFs',
            str_contains($mimeType, 'spreadsheet') || str_contains($mimeType, 'excel') => 'Spreadsheets',
            str_contains($mimeType, 'document') || str_contains($mimeType, 'word') => 'Documents',
            str_starts_with($mimeType, 'text/') => 'Text Files',
            default => 'Other',
        };
    }
}
