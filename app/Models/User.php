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
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'nickname',
        'email',
        'password',
        'description',
        'oauth_provider_id',
        'oauth_provider',
        'oauth_provider_token',
        'oauth_provider_refresh_token',
        'xp',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'oauth_provider_token',
        'oauth_provider_refresh_token'
    ];

    protected $appends = ['avatar'];

    public static function count(): int
    {
        return self::query()->count();
    }

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
                $connectedProviders = $this->providers->keyBy('provider');

                if ($connectedProviders->has('github')) {
                    $p = $connectedProviders->get('github');
                    return [
                        'url' => "https://avatars.githubusercontent.com/u/{$p->provider_id}?v=4",
                        'provider' => 'GitHub',
                        'label' => 'Using GitHub avatar'
                    ];
                }

                if ($connectedProviders->has('gitlab')) {
                    $p = $connectedProviders->get('gitlab');
                    return [
                        'url' => "https://gitlab.com/uploads/-/system/user/avatar/{$p->provider_id}/avatar.png",
                        'provider' => 'GitLab',
                        'label' => 'Using GitLab avatar'
                    ];
                }

                if ($connectedProviders->has('bitbucket')) {
                    $p = $connectedProviders->get('bitbucket');
                    return [
                        'url' => "https://bitbucket.org/account/{$p->provider_username}/avatar/64/",
                        'provider' => 'Bitbucket',
                        'label' => 'Using Bitbucket avatar'
                    ];
                }

                return [
                    'url' => "https://ui-avatars.com/api/?name=" . urlencode($this->name),
                    'provider' => 'system',
                    'label' => 'No provider connected'
                ];
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
        return $this->belongsToMany(User::class, 'followers', 'followed_id', 'user_id')
            ->withTimestamps();
    }

    public function followings(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'followers', 'user_id', 'followed_id')
            ->withTimestamps();
    }

    public function isFollowing(User $user): bool
    {
        return $this->followings()->whereKey($user->id)->exists();
    }

    public function reviewsReceived() {
        return $this->hasMany(Review::class, 'reviewee_id');
    }

    public function providers(): HasMany
    {
        return $this->hasMany(UserProvider::class);
    }

}
