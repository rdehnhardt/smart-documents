<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

class Document extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentFactory> */
    use HasFactory, Searchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'original_name',
        'mime_type',
        'size_bytes',
        'storage_disk',
        'storage_path',
        'visibility',
        'public_token',
        'public_enabled_at',
        'public_disabled_at',
        'title',
        'description',
        'tags',
        'ai_summary',
        'sensitivity',
        'ai_analyzed',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'formatted_size',
        'extension',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'size_bytes' => 'integer',
            'public_enabled_at' => 'datetime',
            'public_disabled_at' => 'datetime',
            'ai_analyzed' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the document.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the users this document is shared with.
     *
     * @return BelongsToMany<User, $this>
     */
    public function sharedWith(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'document_shares')
            ->withPivot(['shared_by', 'can_download'])
            ->withTimestamps();
    }

    /**
     * Check if this document is shared with a specific user.
     */
    public function isSharedWith(User $user): bool
    {
        return $this->sharedWith()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if a user can download this shared document.
     */
    public function canUserDownload(User $user): bool
    {
        if ($this->user_id === $user->id) {
            return true;
        }

        $share = $this->sharedWith()->where('user_id', $user->id)->first();

        return $share?->pivot->can_download ?? false;
    }

    /**
     * Determine if the document is public.
     */
    public function isPublic(): bool
    {
        return $this->visibility === 'public' && $this->public_token !== null;
    }

    /**
     * Determine if the document is private.
     */
    public function isPrivate(): bool
    {
        return $this->visibility === 'private';
    }

    /**
     * Make the document public by generating a token.
     */
    public function makePublic(): void
    {
        $this->update([
            'visibility' => 'public',
            'public_token' => $this->public_token ?? $this->generatePublicToken(),
            'public_enabled_at' => now(),
            'public_disabled_at' => null,
        ]);
    }

    /**
     * Make the document private.
     */
    public function makePrivate(): void
    {
        $this->update([
            'visibility' => 'private',
            'public_disabled_at' => now(),
        ]);
    }

    /**
     * Generate a high-entropy public token.
     */
    public function generatePublicToken(): string
    {
        return Str::random(64);
    }

    /**
     * Get the public URL for the document.
     */
    public function getPublicUrl(): ?string
    {
        if (! $this->isPublic()) {
            return null;
        }

        return url("/p/{$this->public_token}");
    }

    /**
     * Get the formatted file size.
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size_bytes;

        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' KB';
        }

        if ($bytes < 1073741824) {
            return round($bytes / 1048576, 1).' MB';
        }

        return round($bytes / 1073741824, 1).' GB';
    }

    /**
     * Get the file extension from the original name.
     */
    public function getExtensionAttribute(): string
    {
        return pathinfo($this->original_name, PATHINFO_EXTENSION);
    }

    /**
     * Determine if the document is sensitive.
     */
    public function isSensitive(): bool
    {
        return $this->sensitivity === 'sensitive';
    }

    /**
     * Determine if the document may be sensitive.
     */
    public function isMaybeSensitive(): bool
    {
        return $this->sensitivity === 'maybe_sensitive';
    }

    /**
     * Scope to filter by visibility.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Document>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Document>
     */
    public function scopeVisibility($query, string $visibility)
    {
        return $query->where('visibility', $visibility);
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'title' => $this->title,
            'original_name' => $this->original_name,
            'description' => $this->description,
            'ai_summary' => $this->ai_summary,
            'tags' => is_array($this->tags) ? implode(' ', $this->tags) : $this->tags,
        ];
    }
}
