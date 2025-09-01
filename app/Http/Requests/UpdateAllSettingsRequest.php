<?php

namespace App\Http\Requests;

use App\Helpers\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAllSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->role === UserRole::ADMIN->value;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'xp_settings.base_xp' => 'sometimes|integer|min:1|max:10000',
            'xp_settings.bonus_multiplier' => 'sometimes|numeric|min:0.1|max:10',
            'thresholds' => 'sometimes|array',
            'thresholds.*' => 'integer|min:0',
            'skill_weights' => 'sometimes|array',
            'skill_weights.*.skill_name' => 'string|max:255',
            'skill_weights.*.multiplier' => 'numeric|min:0|max:10'
        ];
    }
}
