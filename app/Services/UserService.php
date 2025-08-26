<?php
namespace App\Services;

use App\Models\User;

class UserService{
    public static function listUser(string $term = ''){
        return User::query()
            ->when($term !== '', function ($query) use ($term) {
                $query->where('nickname', 'like', "%{$term}%");
            })->orderBy('nickname')
            ->paginate(30)
            ->withQueryString()
            ->through(fn($user) => [
                'id' => $user->id,
                'nickname' => $user->nickname,
                'avatar' => $user->avatar,
                'name' => $user->name,
            ]);
    }
}


