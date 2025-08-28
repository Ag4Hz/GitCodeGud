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

    public static function rules(): array
    {
        return [
            'level' => 'required|integer|unique:level_thresholds,level',
            'xp_required' => 'required|integer|min:0',
        ];
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
