<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Card;
use App\Models\CardView;
use App\Support\ApiResponse;
use DateTimeImmutable;
use Illuminate\Database\Capsule\Manager as DB;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class StatsController
{
    public function dashboard(Request $request, Response $response): Response
    {
        $userId = (int) $request->getAttribute('userId');

        $cardIds = Card::where('user_id', $userId)->pluck('id');

        if ($cardIds->isEmpty()) {
            return ApiResponse::success($response, [
                'totalViews'      => 0,
                'viewsToday'      => 0,
                'viewsThisMonth'  => 0,
                'viewsPerCard'    => [],
                'viewsLast30Days' => $this->zeroFilledLast30Days(),
            ]);
        }

        $totalViews = CardView::whereIn('card_id', $cardIds)->count();

        $today = (new DateTimeImmutable('today'))->format('Y-m-d 00:00:00');
        $viewsToday = CardView::whereIn('card_id', $cardIds)
            ->where('viewed_at', '>=', $today)
            ->count();

        $monthStart = (new DateTimeImmutable('first day of this month'))->format('Y-m-d 00:00:00');
        $viewsThisMonth = CardView::whereIn('card_id', $cardIds)
            ->where('viewed_at', '>=', $monthStart)
            ->count();

        $viewsPerCard = DB::table('card_views')
            ->join('cards', 'cards.id', '=', 'card_views.card_id')
            ->whereIn('card_views.card_id', $cardIds)
            ->selectRaw('cards.id as cardId, cards.name as name, COUNT(card_views.id) as count')
            ->groupBy('cards.id', 'cards.name')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'cardId' => (int) $row->cardId,
                'name'   => $row->name,
                'count'  => (int) $row->count,
            ])
            ->all();

        $viewsLast30Days = $this->viewsLast30Days($cardIds->all());

        return ApiResponse::success($response, [
            'totalViews'      => $totalViews,
            'viewsToday'      => $viewsToday,
            'viewsThisMonth'  => $viewsThisMonth,
            'viewsPerCard'    => $viewsPerCard,
            'viewsLast30Days' => $viewsLast30Days,
        ]);
    }

    /**
     * Always returns exactly 30 entries (oldest -> today), zero-filled for days with no views.
     */
    private function viewsLast30Days(array $cardIds): array
    {
        $start = (new DateTimeImmutable('today'))->modify('-29 days');

        $rows = DB::table('card_views')
            ->whereIn('card_id', $cardIds)
            ->where('viewed_at', '>=', $start->format('Y-m-d 00:00:00'))
            ->selectRaw('DATE(viewed_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->pluck('count', 'day');

        $result = [];
        for ($i = 0; $i < 30; $i++) {
            $date = $start->modify("+{$i} days")->format('Y-m-d');
            $result[] = [
                'date'  => $date,
                'count' => isset($rows[$date]) ? (int) $rows[$date] : 0,
            ];
        }

        return $result;
    }

    private function zeroFilledLast30Days(): array
    {
        $start = (new DateTimeImmutable('today'))->modify('-29 days');
        $result = [];

        for ($i = 0; $i < 30; $i++) {
            $result[] = [
                'date'  => $start->modify("+{$i} days")->format('Y-m-d'),
                'count' => 0,
            ];
        }

        return $result;
    }
}
