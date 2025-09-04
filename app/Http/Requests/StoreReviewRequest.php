<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Rules\CanReviewUser;
use App\Services\ReviewService;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'reviewee_id' => [
                'required',
                'integer',
                'exists:users,id',
                Rule::notIn([$this->user()->id]),
                new CanReviewUser(app(ReviewService::class)),
            ],
            'comment' => ['required', 'string', 'min:3', 'max:4096'],
        ];
    }
}
