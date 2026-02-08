<?php

namespace App\Http\Requests\Documents;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $document = $this->route('document');

        return $document && $this->user()?->can('update', $document);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['string', 'max:50'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.max' => 'The title must not exceed 255 characters.',
            'description.max' => 'The description must not exceed 2000 characters.',
            'tags.max' => 'You can add up to 10 tags.',
            'tags.*.max' => 'Each tag must not exceed 50 characters.',
        ];
    }
}
