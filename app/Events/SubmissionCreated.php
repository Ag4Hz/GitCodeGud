<?php

namespace App\Events;

use App\Models\Submission;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubmissionCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Submission $submission
    ) {}

    public function broadcastOn(): array
    {
        $ownerId = $this->submission->bounty->issue->repo->user_id;

        return [
            new PrivateChannel("user.{$ownerId}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'bounty_id'    => $this->submission->bounty_id,
            'bounty_title' => $this->submission->bounty->title,
            'submitter'    => $this->submission->user->nickname ?? $this->submission->user->name,
            'pr_url'       => $this->submission->pr_url,
            'submitted_at' => $this->submission->created_at,
        ];
    }
}
