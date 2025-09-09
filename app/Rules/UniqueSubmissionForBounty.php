<?php

namespace App\Rules;

use App\Models\Bounty;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueSubmissionForBounty implements ValidationRule, DataAwareRule
{
    protected $data = [];

    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $bountyId = $this->data['bounty_id'] ?? null;
        if (!$bountyId) {
            return;
        }

        $bounty = Bounty::find($bountyId);
        if (!$bounty) {
            return;
        }

        $existingSubmission = $bounty->submissions()
            ->where('user_id', auth()->id())
            ->first();

        if ($existingSubmission && $existingSubmission->status !== 'rejected') {
            $fail('You have already submitted a solution for this bounty.');
        }
    }
}
