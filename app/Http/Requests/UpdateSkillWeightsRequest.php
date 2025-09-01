<?php

namespace App\Http\Requests;

use App\Helpers\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSkillWeightsRequest extends FormRequest
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
            'skill_weights' => 'required|array',
            'skill_weights.*.skill_name' => 'required|string|max:255',
            'skill_weights.*.multiplier' => 'required|numeric|min:0.1|max:10'
        ];
    }
}
