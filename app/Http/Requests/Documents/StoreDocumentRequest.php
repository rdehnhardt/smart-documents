<?php

namespace App\Http\Requests\Documents;

use App\Services\DocumentStorageService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                File::default()->max(DocumentStorageService::MAX_FILE_SIZE / 1024),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maxSizeMb = DocumentStorageService::MAX_FILE_SIZE / (1024 * 1024);

        return [
            'file.required' => 'Please select a file to upload.',
            'file.max' => "The file size must not exceed {$maxSizeMb}MB.",
            'title.max' => 'The title must not exceed 255 characters.',
            'description.max' => 'The description must not exceed 2000 characters.',
        ];
    }
}
