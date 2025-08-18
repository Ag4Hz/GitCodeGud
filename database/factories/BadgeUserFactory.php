<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Badge;
use App\Models\BadgeUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BadgeUser>
 */
class BadgeUserFactory extends Factory
{
    protected $model = BadgeUser::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'badge_id' => Badge::factory(),
        ];
    }
}
