<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeaderboardUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly int $newXP
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('leaderboard'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'user_id'  => $this->user->id,
            'nickname' => $this->user->nickname ?? $this->user->name,
            'avatar'   => $this->user->avatar,
            'new_xp'   => $this->newXP,
        ];
    }
}
