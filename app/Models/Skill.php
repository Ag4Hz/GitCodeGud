<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Skill extends Model
{
    Use HasFactory;
    protected $fillable = [
        'skill_name',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'type' => 'string',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this -> belongsToMany(User::class,'user_skills')
            ->withPivot('xp','level')
            ->withTimestamps();
    }
}
