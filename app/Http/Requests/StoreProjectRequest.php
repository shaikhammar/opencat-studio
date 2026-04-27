<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'source_lang' => 'required|string|max:20',
            'target_lang' => 'required|string|max:20',
            'use_global_tm' => 'boolean',
            'create_project_tm' => 'boolean',
            'create_project_glossary' => 'boolean',
            'mt_provider' => 'nullable|in:deepl,google,azure',
            'mt_prefill' => 'boolean',
            'char_limit_per_segment' => 'nullable|integer|min:1|max:10000',
            'char_limit_warning_pct' => 'integer|min:1|max:100',
            'tm_min_match_pct' => 'integer|min:0|max:100',
            'files.*' => 'nullable|file|max:51200',
        ];
    }
}
