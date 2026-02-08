<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

#[UseCheapestModel]
#[MaxTokens(8192)]
class DocumentAnalyzer implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
You are a document analyzer that examines uploaded documents and provides:

1. A suggested title (concise, descriptive, max 100 characters)
2. A short description (1-2 sentences summarizing the content)
3. Relevant tags (up to 5 tags for categorization)
4. A brief summary (2-3 paragraphs capturing key points)
5. A sensitivity classification:
   - "safe": No sensitive information detected
   - "maybe_sensitive": Contains potentially sensitive information (personal data, financial info, etc.)
   - "sensitive": Contains highly sensitive information (passwords, medical records, confidential business data, etc.)

Be accurate and helpful. For sensitivity classification, err on the side of caution - if in doubt, classify as "maybe_sensitive".

If the document is an image, describe what you see and classify accordingly.
If the document content cannot be extracted or is empty, provide reasonable defaults based on the filename.
INSTRUCTIONS;
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('A concise, descriptive title for the document')->required(),
            'description' => $schema->string()->description('A short 1-2 sentence description of the document')->required(),
            'tags' => $schema->array()->items($schema->string())->description('Up to 5 relevant tags for categorization')->required(),
            'summary' => $schema->string()->description('A 2-3 paragraph summary of the document content')->required(),
            'sensitivity' => $schema->string()->description('Sensitivity classification. Must be exactly one of: "safe", "maybe_sensitive", or "sensitive"')->required(),
        ];
    }
}
