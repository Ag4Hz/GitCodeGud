<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkillUser extends Model
{
    use HasFactory;

    protected $table = 'user_skills';
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'skill_id',
        'xp',
        'level',
    ];

    protected function casts():array
    {
        return[
            'xp'=>'integer',
            'level'=>'integer',
        ];
    }

    //Get the user that owns this skill.
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }


}
