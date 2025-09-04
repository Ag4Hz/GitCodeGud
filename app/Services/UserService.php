<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class UserService
{
    public static function listUser(string $term = '', ?int $page = 1, ?int $perPage = 30): LengthAwarePaginator
    {
        return User::query()
            ->when($term, function ($query) use ($term) {
                $query->where('nickname', 'like', "%{$term}%");
            })
            ->orderBy('nickname')
            ->paginate(20, ['*'], 'users_page', $page ?? 1)
            ->withQueryString()
            ->through(fn ($user) => [
                'id' => $user->id,
                'nickname' => $user->nickname,
                'avatar' => $user->avatar,
                'name' => $user->name,
            ]);
    }

    public static function searchUser($users): array
    {
        return [
            'users' => $users,
        ];
    }
}
