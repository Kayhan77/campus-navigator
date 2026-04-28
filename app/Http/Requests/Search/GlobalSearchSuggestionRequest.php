<?php

namespace App\Http\Requests\Search;

use Illuminate\Foundation\Http\FormRequest;

class GlobalSearchSuggestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:1', 'max:' . config('search.max_search_length', 100)],
        ];
    }

    public function term(): string
    {
        return (string) $this->input('q');
    }
}
