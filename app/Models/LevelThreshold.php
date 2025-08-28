<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LevelThreshold extends Model
{
    use HasFactory;

    protected $fillable = [
        'level',
        'xp_required',
    ];

    protected $casts = [
        'level' => 'integer',
        'xp_required' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Ensure level is unique
    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($threshold) {
            if (static::where('level', $threshold->level)->exists()) {
                throw new \Exception("Level {$threshold->level} threshold already exists");
            }
        });
    }

    public static function getThresholds(): array
    {
        return static::orderBy('level')->pluck('xp_required', 'level')->toArray();
    }

    public static function updateThresholds(array $thresholds): void
    {
        static::query()->delete();

        foreach ($thresholds as $index => $xpRequired) {
            static::create([
                'level' => $index + 1,
                'xp_required' => $xpRequired,
            ]);
        }
    }
}
