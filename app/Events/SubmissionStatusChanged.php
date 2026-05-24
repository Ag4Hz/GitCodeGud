<?php

namespace App\Events;

use App\Models\Submission;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubmissionStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Submission $submission
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->submission->user_id}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'submission_id' => $this->submission->id,
            'bounty_id'     => $this->submission->bounty_id,
            'status'        => $this->submission->status,
        ];
    }
}
