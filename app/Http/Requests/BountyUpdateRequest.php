<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
class BountyUpdateRequest extends FormRequest
{
    public function authorize():bool
    {
        return auth()->check();
    }
    public function rules():array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'reward_xp' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
