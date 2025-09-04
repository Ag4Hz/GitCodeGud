<?php

namespace App\Rules;

use App\Models\Bounty;
use App\Models\Issue;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueIssueForBounty implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $existingIssue = Issue::where('url', $value)->first();

        if ($existingIssue) {
            $existingBounty = Bounty::where('issue_id', $existingIssue->id)->exists();
            if ($existingBounty) {
                $fail('A bounty already exists for this GitHub issue. Each issue can only have one bounty.');
            }
        }
    }
}
