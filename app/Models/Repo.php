<?php

namespace App\Models;

use Database\Factories\RepoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Repo extends Model
{
    /** @use HasFactory<RepoFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'description',
        'url',
        'git_id'
    ];


    public function users(): BelongsTo
    {
        return $this->BelongsTo(User::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }
}
