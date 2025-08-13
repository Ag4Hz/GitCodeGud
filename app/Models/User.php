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
    public function repos(): BelongsToMany
    {
        return $this -> belongsToMany(Repo::class,'user_repos')
                     -> withPivot('role')
                     -> withTimestamps();
    }

    //Users - User_Skills
    public function skills(): BelongsToMany
    {
        return $this -> belongsToMany(Skill::class,'user_skills')
                     -> withPivot('xp','level')
                     -> withTimestamps();
    }

    //Users - User_Badges
    public function badges(): BelongsToMany
    {
        return $this -> belongsToMany(Badge::class,'user_badges')
                     ->withTimestamps();
    }

    //Users Submissions
    public function submissions(): HasMany
    {
        return $this -> hasMany(Submission::class);
    }

    // Users - Users_Review
    // Get reviews this user has written (as reviewer).
    public function reviewsGiven(): BelongsToMany
    {
        return $this->belongsToMany(Review::class, 'users_review', 'reviewer_id', 'review_id')
            ->withPivot('reviewee_id')
            ->withTimestamps();
    }

    // Users - Users_Review
    // Get reviews this user has received (as reviewee).
    public function reviewsReceived(): BelongsToMany
    {
        return $this->belongsToMany(Review::class, 'users_review', 'reviewee_id', 'review_id')
            ->withPivot('reviewer_id')
            ->withTimestamps();
    }

    //Users - Followers
    public function followers():HasMany
    {
        return $this->hasMany(Follower::class,'user_id');
    }

    public function following():HasMany
    {
        return $this ->hasMany(Follower::class,'follower_user_id');
    }


}
