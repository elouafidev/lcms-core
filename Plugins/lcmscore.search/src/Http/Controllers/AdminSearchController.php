<?php

declare(strict_types=1);

namespace Plugins\Search\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Plugins\Search\Models\SearchLog;

class AdminSearchController extends Controller
{
    /**
     * Display search analytics dashboard and query logs.
     */
    public function index(): View
    {
        $totalSearches = SearchLog::query()->count();
        $zeroResultSearches = SearchLog::query()->where('results_count', 0)->count();

        // Top searched queries
        $topQueries = SearchLog::query()
            ->select('query', DB::raw('count(*) as count'), DB::raw('avg(results_count) as avg_results'))
            ->groupBy('query')
            ->orderByDesc('count')
            ->take(10)
            ->get();

        // Top queries with 0 results (content opportunities)
        $zeroQueries = SearchLog::query()
            ->select('query', DB::raw('count(*) as count'), DB::raw('max(created_at) as last_searched'))
            ->where('results_count', 0)
            ->groupBy('query')
            ->orderByDesc('count')
            ->take(10)
            ->get();

        // Recent search logs
        $recentLogs = SearchLog::query()->latest()->paginate(20);

        return view('search::admin.index', compact(
            'totalSearches',
            'zeroResultSearches',
            'topQueries',
            'zeroQueries',
            'recentLogs'
        ));
    }

    /**
     * Clear all search query logs.
     */
    public function clearLogs(): RedirectResponse
    {
        SearchLog::query()->delete();

        return redirect('/admin/search')->with('success', 'Historique des recherches effacé avec succès.');
    }
}
