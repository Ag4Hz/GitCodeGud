<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BountyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'reward_xp' => $this->reward_xp,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'languages' => $this->languages,
            'issue' => [
                'url' => $this->issue->url,
                'repo' => [
                    'url' => $this->issue->repo->url,
                ],
            ],
            'submissions_count' => $this->submissions_count ?? $this->submissions->count(),
        ];
    }
}
