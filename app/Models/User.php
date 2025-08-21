<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'nickname',
        'email',
        'password',
        'description',
        'oauth_provider_id',
        'oauth_provider',
        'oauth_provider_token',
        'oauth_provider_refresh_token'
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'oauth_provider_token',
        'oauth_provider_refresh_token'
    ];
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'xp' => 'integer',
        ];
    }

    protected function avatar(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->oauth_provider !== 'github' || !$this->oauth_provider_id) {
                    return null;
                }

                return "https://avatars.githubusercontent.com/u/{$this->oauth_provider_id}?v=4";
            },
        );
    }

    //Users - Repos
    public function repos(): HasMany
    {
        return $this -> hasMany(Repo::class);
    }

    //Users - Reviews
    public function reviewsAsReviewer(): HasMany
    {
        return $this->hasMany(Review::class, 'user_id');
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
        return $this -> belongsToMany(Badge::class)
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
}
