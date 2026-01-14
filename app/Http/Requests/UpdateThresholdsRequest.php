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
            'thresholds.*' => 'required|integer|min:1'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $thresholds = $this->input('thresholds', []);

            // Check if thresholds are in increasing order
            for ($i = 1; $i < count($thresholds); $i++) {
                if ($thresholds[$i] <= $thresholds[$i - 1]) {
                    $validator->errors()->add(
                        'thresholds',
                        'Level thresholds must be in strictly increasing order.'
                    );
                    break;
                }
            }
        });
    }
}
