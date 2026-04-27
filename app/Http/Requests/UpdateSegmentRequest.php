<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSegmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_text' => 'nullable|string',
            'target_tags' => 'array',
            'status' => 'required|in:untranslated,draft,translated,reviewed,approved,rejected',
        ];
    }
}
