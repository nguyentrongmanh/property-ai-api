<?php

namespace App\Http\Requests;

use App\Enums\BuildingStatus;
use App\Enums\BuildingType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexPropertiesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'city' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', Rule::enum(BuildingType::class)],
            'status' => ['sometimes', Rule::enum(BuildingStatus::class)],
            'min_occupancy' => ['sometimes', 'numeric', 'between:0,1'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
