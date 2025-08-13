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

    //Many-to-many relationship through users_review pivot table.
    public function reviewers():BelongsToMany
    {
        return $this -> belongsToMany(User::class, 'users_review', 'review_id', 'reviewer_id')
            ->withPivot('reviewee_id')
            ->withTimestamps();
    }
    public function reviewee():BelongsToMany
    {
        return $this -> belongsToMany(User::class, 'users_review', 'review_id', 'reviewee_id')
                     ->withPivot('reviewee_id')
                     ->withTimestamps();
    }

    //Many-to-many relationship through users_review pivot table.
    public function reviewees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'users_review', 'review_id', 'reviewee_id')
            ->withPivot('reviewer_id')
            ->withTimestamps();
    }

    //gives you access to reviewer, reviewee, and review in one query.
    public function reviewRelationships()
    {
        return $this->hasMany(UsersReview::class, 'review_id');
    }

}
