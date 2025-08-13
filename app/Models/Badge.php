<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Badge extends Model
{
    protected  $fillable = [
        'name',
        'description',
    ];

    protected  function casts():array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function users():BelongsToMany
    {
        return $this->belongsToMany(User::class,'user_badges')
                    ->withTimestamps();
    }
}
