<?php

namespace App\Events;

use App\Models\Bounty;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BountyStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Bounty $bounty,
        public readonly string $oldStatus
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('bounties'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id'         => $this->bounty->id,
            'status'     => $this->bounty->status,
            'old_status' => $this->oldStatus,
        ];
    }
}
