<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsersReview extends Model
{
    use HasFactory;

    protected $table = 'users_review';
    public $timestamps = true;

    public $incrementing = false;

    protected $fillable = [
        'review_id',
        'reviewer_id',
        'reviewee_id',
    ];

    protected function casts(): array
    {
        return[
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function review():BelongsTo
    {
        return $this->belongsTo(Review::class,'review_id');
    }
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
    public function reviewee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }
}
