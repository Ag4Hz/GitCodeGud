<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Review extends Model
{
    use HasFactory;
    protected $fillable = [
        'comment',
        'date',
    ];

    protected function casts():array
    {
        return [
            'date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
    // Users_Review - Review (n-1 kapcsolat)
    public function reviewRelationships(): HasMany
    {
        return $this->hasMany(UsersReview::class, 'review_id');
    }
}
