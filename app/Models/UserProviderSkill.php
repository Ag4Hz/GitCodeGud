<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProviderSkill extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_provider_id',
        'skill_id',
        'xp',
    ];

    protected function casts(): array
    {
        return [
            'xp' => 'integer',
        ];
    }

    public function userProvider(): BelongsTo
    {
        return $this->belongsTo(UserProvider::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }
}

