<?php

namespace App\Http\Requests\Search;

use Illuminate\Foundation\Http\FormRequest;

class GlobalSearchRequest extends FormRequest
{
    private const DEFAULT_PER_MODEL = 5;
    private const MAX_PER_MODEL = 20;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q'         => [
                'required',
                'string',
                'min:' . config('search.min_search_length', 2),
                'max:' . config('search.max_search_length', 100),
            ],
            'per_model' => ['sometimes', 'integer', 'min:1', 'max:' . self::MAX_PER_MODEL],
        ];
    }

    public function term(): string
    {
        return $this->input('q');
    }

    public function perModel(int $default = 5, int $max = 20): int
    {
        $default = min(max($default, 1), self::MAX_PER_MODEL);
        $max = min(max($max, 1), self::MAX_PER_MODEL);

        return min((int) $this->input('per_model', $default ?: self::DEFAULT_PER_MODEL), $max);
    }
}
