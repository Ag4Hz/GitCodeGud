<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BountyStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'reward_xp' => ['required', 'integer', 'min:1', 'max:1000'],
            'repo_url' => ['required', 'url', 'regex:/github\.com\/[^\/]+\/[^\/]+/'],
            'issue_url' => ['required', 'url', 'regex:/github\.com\/[^\/]+\/[^\/]+\/issues\/\d+/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('repo_url')) {
            $this->merge([
                'repo_url' => rtrim(parse_url($this->repo_url, PHP_URL_SCHEME) . '://' .
                    parse_url($this->repo_url, PHP_URL_HOST) .
                    parse_url($this->repo_url, PHP_URL_PATH), '/'),
            ]);
        }

        if ($this->has('issue_url')) {
            $this->merge([
                'issue_url' => rtrim(parse_url($this->issue_url, PHP_URL_SCHEME) . '://' .
                    parse_url($this->issue_url, PHP_URL_HOST) .
                    parse_url($this->issue_url, PHP_URL_PATH), '/'),
            ]);
        }
    }
}
