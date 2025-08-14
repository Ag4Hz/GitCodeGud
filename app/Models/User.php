<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'oauth_provider',
        'xp',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'xp' => 'integer',
        ];
    }
    //Users - Repos
    public function repos(): HasMany
    {
        return $this -> hasMany(Repo::class);
    }
    //Users - Reviews
    public function reviewsAsReviewer(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }
    public function reviewsAsReviewee(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewee_id');
    }
    //Users Submissions
    public function submissions(): HasMany
    {
        return $this -> hasMany(Submission::class);
    }
    //Users - User_Badges
    public function badges(): BelongsToMany
    {
        return $this -> belongsToMany(Badge::class,'user_badges')
                     ->withTimestamps();
    }
    //Users - User_Skills
    public function skills(): BelongsToMany
    {
        return $this -> belongsToMany(Skill::class,'user_skills')
            -> withPivot('xp','level')
            -> withTimestamps();
    }
    //Users - Followers
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'followers', 'followed_id', 'follower_id')
            ->withTimestamps();
    }
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'followers', 'follower_id', 'followed_id')
            ->withTimestamps();
    }
}
