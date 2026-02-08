<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $extension = fake()->randomElement(['pdf', 'png', 'jpg', 'docx', 'xlsx', 'txt']);
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'txt' => 'text/plain',
        ];

        $originalName = fake()->word().'.'.$extension;

        return [
            'user_id' => User::factory(),
            'original_name' => $originalName,
            'mime_type' => $mimeTypes[$extension],
            'size_bytes' => fake()->numberBetween(1024, 20 * 1024 * 1024),
            'storage_disk' => 'documents',
            'storage_path' => 'documents/'.Str::uuid().'.'.$extension,
            'visibility' => 'private',
            'public_token' => null,
            'public_enabled_at' => null,
            'public_disabled_at' => null,
            'title' => fake()->optional(0.7)->sentence(3),
            'description' => fake()->optional(0.5)->paragraph(),
            'tags' => fake()->optional(0.5)->randomElements(['invoice', 'contract', 'report', 'image', 'spreadsheet'], fake()->numberBetween(1, 3)),
            'ai_summary' => null,
            'sensitivity' => null,
            'ai_analyzed' => false,
        ];
    }

    /**
     * Indicate that the document is public.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'public',
            'public_token' => Str::random(64),
            'public_enabled_at' => now(),
            'public_disabled_at' => null,
        ]);
    }

    /**
     * Indicate that the document was analyzed by AI.
     */
    public function analyzed(): static
    {
        return $this->state(fn (array $attributes) => [
            'ai_analyzed' => true,
            'ai_summary' => fake()->paragraph(),
            'sensitivity' => fake()->randomElement(['safe', 'maybe_sensitive', 'sensitive']),
            'title' => $attributes['title'] ?? fake()->sentence(3),
            'description' => $attributes['description'] ?? fake()->paragraph(),
            'tags' => $attributes['tags'] ?? fake()->randomElements(['invoice', 'contract', 'report', 'image'], fake()->numberBetween(1, 3)),
        ]);
    }

    /**
     * Indicate that the document is sensitive.
     */
    public function sensitive(): static
    {
        return $this->state(fn (array $attributes) => [
            'sensitivity' => 'sensitive',
            'ai_analyzed' => true,
            'visibility' => 'private',
        ]);
    }

    /**
     * Indicate that the document may be sensitive.
     */
    public function maybeSensitive(): static
    {
        return $this->state(fn (array $attributes) => [
            'sensitivity' => 'maybe_sensitive',
            'ai_analyzed' => true,
        ]);
    }

    /**
     * Indicate that the document is a PDF.
     */
    public function pdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'original_name' => fake()->word().'.pdf',
            'mime_type' => 'application/pdf',
            'storage_path' => 'documents/'.Str::uuid().'.pdf',
        ]);
    }

    /**
     * Indicate that the document is an image.
     */
    public function image(): static
    {
        $extension = fake()->randomElement(['png', 'jpg', 'jpeg']);
        $mimeTypes = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
        ];

        return $this->state(fn (array $attributes) => [
            'original_name' => fake()->word().'.'.$extension,
            'mime_type' => $mimeTypes[$extension],
            'storage_path' => 'documents/'.Str::uuid().'.'.$extension,
        ]);
    }
}
