<?php

namespace App\Http\Controllers;

use App\Services\BountyRecommendationService;
use App\Models\Bounty;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BountyRecommendationController extends Controller
{
    public function __construct(
        private readonly BountyRecommendationService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['recommendations' => []]);
        }

        $recommendations = $this->service->getRecommendations($user);

        if (empty($recommendations)) {
            return response()->json(['recommendations' => []]);
        }

        $ids = collect($recommendations)->pluck('id');
        $reasons = collect($recommendations)->keyBy('id');

        $bounties = Bounty::with('issue')
            ->whereIn('id', $ids)
            ->where('status', 'open')
            ->get()
            ->map(function ($bounty) use ($reasons) {
                $bounty->recommendation_reason = $reasons[$bounty->id]['reason'] ?? '';
                $bounty->recommendation_category = $reasons[$bounty->id]['category'] ?? 'skill_match';
                return $bounty;
            })
            ->sortBy(fn($b) => $ids->search($b->id))
            ->values()
            ->take(8);

        return response()->json(['recommendations' => $bounties]);
    }
}
