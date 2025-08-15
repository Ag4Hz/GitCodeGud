<?php

namespace App\Models;

use Database\Factories\IssueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Issue extends Model
{
    /** @use HasFactory<IssueFactory> */
    use HasFactory;

    protected $fillable = [
        'repo_id',
        'url',
        'description',
        'name'
    ];


    public function repo(): BelongsTo
    {
        return $this->belongsTo(Repo::class);
    }

    public function bounties(): HasOne
    {
        return $this->hasOne(Bounty::class);
    }
}
