<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => 'sometimes|in:active,completed,archived',
            'qa_config' => 'sometimes|array',
            'mt_provider' => 'nullable|in:deepl,google,azure',
        ];
    }
}
