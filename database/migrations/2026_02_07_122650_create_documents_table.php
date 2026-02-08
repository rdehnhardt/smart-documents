<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size_bytes');
            $table->string('storage_disk')->default('documents');
            $table->string('storage_path');
            $table->enum('visibility', ['private', 'public'])->default('private');
            $table->string('public_token', 64)->nullable()->unique();
            $table->timestamp('public_enabled_at')->nullable();
            $table->timestamp('public_disabled_at')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->json('tags')->nullable();
            $table->text('ai_summary')->nullable();
            $table->enum('sensitivity', ['safe', 'maybe_sensitive', 'sensitive'])->nullable();
            $table->boolean('ai_analyzed')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'visibility']);
            $table->index('public_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
