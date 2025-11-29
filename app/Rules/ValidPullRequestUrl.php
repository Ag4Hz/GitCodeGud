<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidPullRequestUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (
            !str_contains($value, 'github.com') &&
            !str_contains($value, 'gitlab.com') &&
            !str_contains($value, 'bitbucket.org')
        ) {
            $fail('Unsupported pull/merge request provider.');
        }
    }
}
