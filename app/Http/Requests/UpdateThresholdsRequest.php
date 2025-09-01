<?php

namespace App\Http\Requests;

use App\Helpers\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class UpdateThresholdsRequest extends FormRequest
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
            'thresholds' => 'required|array',
            'thresholds.*' => 'required|integer|min:0'
        ];
    }
}
