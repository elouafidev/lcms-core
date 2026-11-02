<?php

declare(strict_types=1);

namespace Plugins\Search\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Plugins\Search\Services\SearchEngine;

class PublicSearchController extends Controller
{
    public function __construct(
        private SearchEngine $searchEngine
    ) {}

    /**
     * Display public full search results page.
     */
    public function index(Request $request): View
    {
        $rawQuery = $request->query('q');
        $query = is_string($rawQuery) ? trim($rawQuery) : '';

        $rawType = $request->query('type');
        $type = is_string($rawType) ? trim($rawType) : 'all';

        $results = [];
        if ($query !== '') {
            $results = $this->searchEngine->search(
                term: $query,
                typeFilter: $type,
                limit: 30,
                recordLog: true,
                ip: $request->ip(),
                userAgent: $request->userAgent()
            );
        }

        $typeCounts = [
            'all' => count($results),
            'page' => count(array_filter($results, fn ($r) => $r->type === 'page')),
            'post' => count(array_filter($results, fn ($r) => $r->type === 'post')),
            'block' => count(array_filter($results, fn ($r) => $r->type === 'block')),
        ];

        return view('search::public.index', compact('results', 'query', 'type', 'typeCounts'));
    }

    /**
     * Return instant live AJAX suggestions.
     */
    public function live(Request $request): JsonResponse
    {
        $rawQuery = $request->query('q');
        $query = is_string($rawQuery) ? trim($rawQuery) : '';

        if ($query === '') {
            return response()->json(['results' => []]);
        }

        $results = $this->searchEngine->search(
            term: $query,
            typeFilter: null,
            limit: 6,
            recordLog: false
        );

        return response()->json([
            'results' => array_map(fn ($item) => $item->toArray(), $results),
        ]);
    }
}
