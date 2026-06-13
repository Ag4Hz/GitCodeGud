<?php

namespace App\Events;

use App\Models\Bounty;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BountyCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Bounty $bounty
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
            'id'          => $this->bounty->id,
            'title'       => $this->bounty->title,
            'description' => $this->bounty->description,
            'reward_xp'   => $this->bounty->reward_xp,
            'status'      => $this->bounty->status,
            'languages'   => $this->bounty->languages,
            'created_at'  => $this->bounty->created_at,
            'views'       => 0,
            'issue'       => [
                'provider' => $this->bounty->issue->provider,
                'url'      => $this->bounty->issue->url,
            ],
            'organization_id' => $this->bounty->organization_id,
        ];
    }
}
