<?php

namespace App\Http\Requests\Search;

use Illuminate\Foundation\Http\FormRequest;

class GlobalSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q'         => ['required', 'string', 'min:' . config('search.min_search_length', 2), 'max:100'],
            'per_model' => ['sometimes', 'integer', 'min:1', 'max:20'],
        ];
    }

    public function term(): string
    {
        return $this->input('q');
    }

    public function perModel(int $default = 5, int $max = 20): int
    {
        return min((int) $this->input('per_model', $default), $max);
    }
}
