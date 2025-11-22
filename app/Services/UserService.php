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
                $query->where(function ($q) use ($term) {
                    // Search by nickname
                    $q->where('nickname', 'ILIKE', "%{$term}%")
                        // OR search by any provider username (GitHub, GitLab, Bitbucket)
                        ->orWhereHas('providers', function ($q) use ($term) {
                            $q->where('provider_username', 'ILIKE', "%{$term}%");
                        });
                });
            })
            ->with('providers')
            ->orderByRaw('LOWER(nickname)')
            ->orderBy('nickname')
            ->paginate(20, ['*'], 'users_page', $page ?? 1)
            ->withQueryString()
            ->through(fn ($user) => [
                'id' => $user->id,
                'nickname' => $user->nickname,
                'avatar' => $user->avatar,
                'name' => $user->name,
                'providers' => $user->providers->map(fn ($p) => [
                    'provider' => $p->provider,
                    'provider_username' => $p->provider_username,
                ])->values(),
            ]);
    }

    public static function searchUser($users): array
    {
        return [
            'users' => $users,
        ];
    }
}
