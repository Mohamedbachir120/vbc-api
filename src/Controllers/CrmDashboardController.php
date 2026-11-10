<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Lead;
use App\Models\User;
use App\Support\ApiResponse;
use DateTimeImmutable;
use Illuminate\Database\Capsule\Manager as DB;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CrmDashboardController
{
    public function dashboard(Request $request, Response $response): Response
    {
        $userId = (int) $request->getAttribute('userId');
        $user = User::find($userId);
        $isAdmin = $user && $user->isAdmin();

        $base = Lead::query();

        if (!$isAdmin) {
            $base->where('assigned_to', $userId);
        }

        $total = (clone $base)->count();

        $byStatus = [];
        foreach (Lead::STATUSES as $status) {
            $byStatus[$status] = (clone $base)->where('status', $status)->count();
        }

        $monthStart = (new DateTimeImmutable('first day of this month'))->format('Y-m-d 00:00:00');
        $leadsThisMonth = (clone $base)->where('created_at', '>=', $monthStart)->count();

        $weekStart = (new DateTimeImmutable('monday this week'))->format('Y-m-d 00:00:00');
        $leadsThisWeek = (clone $base)->where('created_at', '>=', $weekStart)->count();

        $vendu = $byStatus['vendu'] ?? 0;
        $conversionRate = $total > 0 ? round(($vendu / $total) * 100, 1) : 0.0;

        $bySourceQuery = (clone $base)
            ->selectRaw('source, COUNT(*) as count')
            ->groupBy('source')
            ->pluck('count', 'source');
        $bySource = [];
        foreach ($bySourceQuery as $source => $count) {
            $bySource[] = ['source' => $source, 'count' => (int) $count];
        }

        $data = [
            'total'          => $total,
            'byStatus'       => $byStatus,
            'bySource'       => $bySource,
            'leadsThisWeek'  => $leadsThisWeek,
            'leadsThisMonth' => $leadsThisMonth,
            'conversionRate' => $conversionRate,
        ];

        if ($isAdmin) {
            $perCommercial = DB::table('leads')
                ->join('users', 'users.id', '=', 'leads.assigned_to')
                ->selectRaw("users.id as userId, users.name as name, COUNT(*) as total, SUM(CASE WHEN leads.status = 'vendu' THEN 1 ELSE 0 END) as vendu")
                ->groupBy('users.id', 'users.name')
                ->get()
                ->map(fn ($row) => [
                    'userId' => (int) $row->userId,
                    'name'   => $row->name,
                    'total'  => (int) $row->total,
                    'vendu'  => (int) $row->vendu,
                ])
                ->all();

            $data['perCommercial'] = $perCommercial;
        }

        return ApiResponse::success($response, $data);
    }
}
