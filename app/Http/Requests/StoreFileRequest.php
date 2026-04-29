<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $formats = implode(',', config('catframework.file_processing.supported_formats'));

        return [
            'files' => 'required|array|min:1',
            'files.*' => "required|file|max:51200|mimes:{$formats}",
            'mt_prefill' => 'boolean',
        ];
    }
}
