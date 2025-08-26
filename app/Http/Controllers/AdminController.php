<?php

namespace App\Http\Controllers;

use App\Helpers\XPHelper;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function index()
    {
        // Get XP statistics
        $xpStats = [
            'total_users' => $this->getTotalUsers(),
            'users_with_xp' => $this->getUsersWithXP(),
            'total_xp_distributed' => $this->getTotalXPDistributed(),
            'average_xp' => $this->getAverageXP(),
            'highest_xp' => $this->getHighestXP(),
            'level_distribution' => $this->getLevelDistribution(),
        ];

        return Inertia::render('Admin', [
            'xpStats' => $xpStats,
        ]);
    }

    private function getTotalUsers()
    {
        return User::count();
    }

    private function getUsersWithXP()
    {
        return DB::table('user_skills')
            ->distinct('user_id')
            ->count('user_id');
    }

    private function getTotalXPDistributed()
    {
        return DB::table('user_skills')->sum('xp') ?? 0;
    }

    private function getAverageXP()
    {
        $userXPs = DB::table('user_skills')
            ->select('user_id', DB::raw('SUM(xp) as total_xp'))
            ->groupBy('user_id')
            ->pluck('total_xp');

        return $userXPs->count() > 0 ? round($userXPs->avg(), 2) : 0;
    }

    private function getHighestXP()
    {
        return DB::table('user_skills')
            ->select(DB::raw('SUM(xp) as total_xp'))
            ->groupBy('user_id')
            ->orderBy('total_xp', 'desc')
            ->value('total_xp') ?? 0;
    }

    private function getLevelDistribution()
    {
        $userXPs = DB::table('user_skills')
            ->select('user_id', DB::raw('SUM(xp) as total_xp'))
            ->groupBy('user_id')
            ->get();

        $distribution = [];

        foreach ($userXPs as $userXP) {
            if ($userXP->total_xp > 0) {
                $level = XPHelper::calculateLevel($userXP->total_xp);
                $distribution[$level] = ($distribution[$level] ?? 0) + 1;
            }
        }

        // If no users have XP, add a default level 1 entry
        if (empty($distribution)) {
            $distribution[1] = 0;
        }

        ksort($distribution);
        return $distribution;
    }
}
