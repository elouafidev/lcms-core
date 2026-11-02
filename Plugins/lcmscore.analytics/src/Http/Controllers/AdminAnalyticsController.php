<?php

declare(strict_types=1);

namespace Plugins\Analytics\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Plugins\Analytics\Models\PageView;

class AdminAnalyticsController extends Controller
{
    /**
     * Display analytics overview dashboard.
     */
    public function index(Request $request): View
    {
        $rawPeriod = $request->query('period');
        $period = is_string($rawPeriod) && in_array($rawPeriod, ['today', '7d', '30d', 'all'], true)
            ? $rawPeriod
            : '7d';

        $startDate = match ($period) {
            'today' => now()->startOfDay(),
            '7d' => now()->subDays(7)->startOfDay(),
            '30d' => now()->subDays(30)->startOfDay(),
            'all' => null,
        };

        $baseQuery = PageView::query();
        if ($startDate !== null) {
            $baseQuery->where('created_at', '>=', $startDate);
        }

        // 1. KPI Metrics
        $totalPageviews = (clone $baseQuery)->count();
        $uniqueVisitors = (clone $baseQuery)->distinct('visitor_hash')->count('visitor_hash');
        $pagesPerVisitor = $uniqueVisitors > 0
            ? round($totalPageviews / $uniqueVisitors, 1)
            : 0.0;

        // 2. Daily Trends
        $dailyTrends = (clone $baseQuery)
            ->select(
                DB::raw('substr(created_at, 1, 10) as date_key'),
                DB::raw('count(*) as views'),
                DB::raw('count(distinct visitor_hash) as visitors')
            )
            ->groupBy('date_key')
            ->orderBy('date_key')
            ->get();

        // 3. Top 10 Pages
        $topPages = (clone $baseQuery)
            ->select('path', DB::raw('count(*) as views'), DB::raw('count(distinct visitor_hash) as visitors'))
            ->groupBy('path')
            ->orderByDesc('views')
            ->take(10)
            ->get();

        // 4. Top Referrers
        $topReferrers = (clone $baseQuery)
            ->select(
                DB::raw("case when referrer_host is null or referrer_host = '' then 'Direct / Inconnu' else referrer_host end as source"),
                DB::raw('count(*) as views')
            )
            ->groupBy('source')
            ->orderByDesc('views')
            ->take(10)
            ->get();

        // 5. Device Breakdown
        $devices = (clone $baseQuery)
            ->select('device', DB::raw('count(*) as count'))
            ->groupBy('device')
            ->orderByDesc('count')
            ->get();

        // 6. Browser Breakdown
        $browsers = (clone $baseQuery)
            ->select('browser', DB::raw('count(*) as count'))
            ->groupBy('browser')
            ->orderByDesc('count')
            ->take(6)
            ->get();

        return view('analytics::admin.index', compact(
            'period',
            'totalPageviews',
            'uniqueVisitors',
            'pagesPerVisitor',
            'dailyTrends',
            'topPages',
            'topReferrers',
            'devices',
            'browsers'
        ));
    }

    /**
     * Clear all recorded analytics data.
     */
    public function clear(): RedirectResponse
    {
        PageView::query()->delete();

        return redirect('/admin/analytics')->with('success', 'Historique des statistiques d\'audience purgé avec succès.');
    }
}
