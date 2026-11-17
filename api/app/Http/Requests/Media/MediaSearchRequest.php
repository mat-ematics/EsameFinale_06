<?php

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

class MediaSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q'     => 'required|string|min:2|max:100',
            'type'  => 'nullable|in:film,tv-series',
            'limit' => 'nullable|integer|min:1|max:50',
        ];
    }
}