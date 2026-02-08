<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DocumentShareController extends Controller
{
    /**
     * Store a new document share.
     */
    public function store(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('share', $document);

        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'exists:users,email',
                Rule::notIn([$request->user()->email]),
            ],
            'can_download' => ['boolean'],
        ], [
            'email.exists' => 'No user found with this email address.',
            'email.not_in' => 'You cannot share a document with yourself.',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if ($document->isSharedWith($user)) {
            return back()->with('error', 'Document is already shared with this user.');
        }

        $document->sharedWith()->attach($user->id, [
            'shared_by' => $request->user()->id,
            'can_download' => $validated['can_download'] ?? true,
        ]);

        return back()->with('success', "Document shared with {$user->name}.");
    }

    /**
     * Update a document share.
     */
    public function update(Request $request, Document $document, User $user): RedirectResponse
    {
        $this->authorize('share', $document);

        $validated = $request->validate([
            'can_download' => ['required', 'boolean'],
        ]);

        $document->sharedWith()->updateExistingPivot($user->id, [
            'can_download' => $validated['can_download'],
        ]);

        return back()->with('success', 'Share permissions updated.');
    }

    /**
     * Remove a document share.
     */
    public function destroy(Document $document, User $user): RedirectResponse
    {
        $this->authorize('share', $document);

        $document->sharedWith()->detach($user->id);

        return back()->with('success', "Sharing with {$user->name} has been removed.");
    }
}
