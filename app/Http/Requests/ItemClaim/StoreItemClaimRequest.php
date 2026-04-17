<?php

namespace App\Http\Requests\ItemClaim;

use Illuminate\Foundation\Http\FormRequest;

class StoreItemClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lost_item_id' => 'required|exists:lost_items,id',
            'message' => 'nullable|string',
            'location_found' => 'nullable|string|max:255',
        ];
    }
}
