<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tag_consistency' => 'boolean',
            'length_ratio' => 'boolean',
            'trailing_spaces' => 'boolean',
            'double_spaces' => 'boolean',
            'terminology' => 'boolean',
            'number_consistency' => 'boolean',
            'punctuation_parity' => 'boolean',
            'length_ratio_max' => 'numeric|min:1|max:10',
        ];
    }
}
