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
    ];

    protected function casts(): array
    {
        return[
          'created_at' => 'datetime',
          'updated_at' => 'datetime',
        ];
    }

    //Many-to-many relationship with User through user_skills pivot table.
    public function users(): BelongsToMany
    {
        return $this -> belongsToMany(User::class)
            ->withPivot('xp','level')
            ->withTimestamps();
    }
}
