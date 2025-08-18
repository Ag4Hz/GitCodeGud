<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'reviewee_id',
        'comment',
        'date',
    ];

    protected function casts():array
    {
        return [
            'date' => 'date',
        ];
    }
    // Review - Users (reviewer)
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    // Review - Users (reviewee)
    public function reviewee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }
}
