<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class GeneralSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Accessor to get the properly typed value
    protected function typedValue(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match ($this->type) {
                    'integer' => (int) $this->value,
                    'float' => (float) $this->value,
                    'boolean' => (bool) $this->value,
                    default => $this->value,
                };
            }
        );
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->typed_value : $default;
    }

    public static function setValue(string $key, mixed $value, string $type = 'string'): bool
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => (string) $value,
                'type' => $type,
            ]
        )->exists;
    }
}
