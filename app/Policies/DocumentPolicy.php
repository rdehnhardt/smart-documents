<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    /**
     * Determine whether the user can view any documents.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the document.
     */
    public function view(User $user, Document $document): bool
    {
        return $user->id === $document->user_id || $document->isSharedWith($user);
    }

    /**
     * Determine whether the user can create documents.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the document.
     */
    public function update(User $user, Document $document): bool
    {
        return $user->id === $document->user_id;
    }

    /**
     * Determine whether the user can delete the document.
     */
    public function delete(User $user, Document $document): bool
    {
        return $user->id === $document->user_id;
    }

    /**
     * Determine whether the user can download the document.
     */
    public function download(User $user, Document $document): bool
    {
        return $user->id === $document->user_id || $document->canUserDownload($user);
    }

    /**
     * Determine whether the user can share the document.
     */
    public function share(User $user, Document $document): bool
    {
        return $user->id === $document->user_id;
    }

    /**
     * Determine whether the user can change the document's visibility.
     */
    public function changeVisibility(User $user, Document $document): bool
    {
        if ($user->id !== $document->user_id) {
            return false;
        }

        if ($document->isSensitive()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can restore the document.
     */
    public function restore(User $user, Document $document): bool
    {
        return $user->id === $document->user_id;
    }

    /**
     * Determine whether the user can permanently delete the document.
     */
    public function forceDelete(User $user, Document $document): bool
    {
        return $user->id === $document->user_id;
    }
}
