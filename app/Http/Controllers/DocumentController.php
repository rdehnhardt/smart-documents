<?php

namespace App\Http\Controllers;

use App\Http\Requests\Documents\StoreDocumentRequest;
use App\Http\Requests\Documents\UpdateDocumentRequest;
use App\Jobs\AnalyzeDocumentJob;
use App\Models\Document;
use App\Services\DocumentStorageService;
use App\Services\QrCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(
        protected DocumentStorageService $storageService,
        protected QrCodeService $qrCodeService
    ) {}

    /**
     * Display a listing of the user's documents.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search', '');
        $visibility = $request->input('visibility', '');
        $filter = $request->input('filter', 'owned');
        $user = $request->user();

        if ($filter === 'shared') {
            $query = $user->sharedDocuments()->with('user:id,name')->latest();
            $documents = $query->paginate(12)->withQueryString();
        } elseif ($search) {
            $query = Document::search($search)
                ->query(function ($builder) use ($user, $visibility) {
                    $builder->where('user_id', $user->id);

                    if ($visibility) {
                        $builder->where('visibility', $visibility);
                    }

                    return $builder->latest();
                });

            $documents = $query->paginate(12)->withQueryString();
        } else {
            $query = $user->documents()->latest();

            if ($visibility) {
                $query->where('visibility', $visibility);
            }

            $documents = $query->paginate(12)->withQueryString();
        }

        $sharedCount = $user->sharedDocuments()->count();

        return Inertia::render('documents/index', [
            'documents' => $documents,
            'sharedCount' => $sharedCount,
            'filters' => [
                'search' => $search,
                'visibility' => $visibility,
                'filter' => $filter,
            ],
        ]);
    }

    /**
     * Show the form for creating a new document.
     */
    public function create(): Response
    {
        return Inertia::render('documents/create', [
            'maxFileSize' => DocumentStorageService::MAX_FILE_SIZE,
        ]);
    }

    /**
     * Store a newly created document in storage.
     */
    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $document = $this->storageService->store(
            $request->file('file'),
            $request->user(),
            $request->input('title'),
            $request->input('description')
        );

        AnalyzeDocumentJob::dispatch($document);

        return to_route('documents.show', $document)
            ->with('success', 'Document uploaded successfully. AI analysis is processing.');
    }

    /**
     * Display the specified document.
     */
    public function show(Request $request, Document $document): Response
    {
        $this->authorize('view', $document);

        $user = $request->user();
        $isOwner = $user->id === $document->user_id;

        $qrCode = null;
        if ($document->isPublic()) {
            $qrCode = $this->qrCodeService->generateDataUri($document);
        }

        $sharedWith = $isOwner
            ? $document->sharedWith()->select('users.id', 'users.name', 'users.email')->get()
            : [];

        return Inertia::render('documents/show', [
            'document' => $document->load('user:id,name'),
            'qrCode' => $qrCode,
            'publicUrl' => $document->getPublicUrl(),
            'isOwner' => $isOwner,
            'sharedWith' => $sharedWith,
            'canDownload' => $user->can('download', $document),
        ]);
    }

    /**
     * Show the form for editing the specified document.
     */
    public function edit(Document $document): Response
    {
        $this->authorize('update', $document);

        return Inertia::render('documents/edit', [
            'document' => $document,
        ]);
    }

    /**
     * Update the specified document in storage.
     */
    public function update(UpdateDocumentRequest $request, Document $document): RedirectResponse
    {
        $document->update($request->validated());

        return to_route('documents.show', $document)
            ->with('success', 'Document updated successfully.');
    }

    /**
     * Remove the specified document from storage.
     */
    public function destroy(Document $document): RedirectResponse
    {
        $this->authorize('delete', $document);

        $this->storageService->delete($document);

        return to_route('documents.index')
            ->with('success', 'Document deleted successfully.');
    }

    /**
     * Download the specified document.
     */
    public function download(Document $document): StreamedResponse
    {
        $this->authorize('download', $document);

        return $this->storageService->download($document);
    }

    /**
     * Make the specified document public.
     */
    public function makePublic(Document $document): RedirectResponse
    {
        $this->authorize('changeVisibility', $document);

        $document->makePublic();

        return back()->with('success', 'Document is now public. Share the link below.');
    }

    /**
     * Make the specified document private.
     */
    public function makePrivate(Document $document): RedirectResponse
    {
        $this->authorize('changeVisibility', $document);

        $document->makePrivate();

        return back()->with('success', 'Document is now private.');
    }

    /**
     * Apply AI suggestions to the document.
     */
    public function applySuggestions(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('update', $document);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['string', 'max:50'],
        ]);

        $document->update(array_filter($validated));

        return back()->with('success', 'AI suggestions applied successfully.');
    }

    /**
     * Re-run AI analysis on the document.
     */
    public function reanalyze(Document $document): RedirectResponse
    {
        $this->authorize('update', $document);

        $document->update(['ai_analyzed' => false]);

        AnalyzeDocumentJob::dispatch($document, forceUpdate: true);

        return back()->with('success', 'AI analysis has been queued.');
    }
}
