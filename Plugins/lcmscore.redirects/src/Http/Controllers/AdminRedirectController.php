<?php

declare(strict_types=1);

namespace Plugins\Redirects\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Plugins\Redirects\Models\NotFoundLog;
use Plugins\Redirects\Models\RedirectRule;

class AdminRedirectController extends Controller
{
    /**
     * Display redirects list and 404 logs dashboard.
     */
    public function index(Request $request): View
    {
        $rawTab = $request->query('tab');
        $tab = is_string($rawTab) && in_array($rawTab, ['redirects', 'logs404'], true) ? $rawTab : 'redirects';

        $rawSearch = $request->query('search');
        $search = is_string($rawSearch) ? trim($rawSearch) : '';

        // KPI Counts
        $totalRedirects = RedirectRule::query()->count();
        $activeRedirects = RedirectRule::query()->where('is_active', true)->count();
        $totalHits = (int) RedirectRule::query()->sum('hit_count');
        $total404 = NotFoundLog::query()->count();
        $total404Hits = (int) NotFoundLog::query()->sum('hits');

        // Redirect Rules Query
        $rulesQuery = RedirectRule::query()->latest('id');
        if ($search !== '' && $tab === 'redirects') {
            $rulesQuery->where(function ($q) use ($search): void {
                $q->where('source_path', 'like', '%'.$search.'%')
                    ->orWhere('target_url', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }
        $redirects = $rulesQuery->paginate(25, ['*'], 'redirects_page')->withQueryString();

        // 404 Logs Query
        $logsQuery = NotFoundLog::query()->orderByDesc('hits')->latest('last_hit_at');
        if ($search !== '' && $tab === 'logs404') {
            $logsQuery->where('path', 'like', '%'.$search.'%');
        }
        $logs404 = $logsQuery->paginate(25, ['*'], 'logs_page')->withQueryString();

        return view('redirects::admin.index', compact(
            'tab',
            'search',
            'totalRedirects',
            'activeRedirects',
            'totalHits',
            'total404',
            'total404Hits',
            'redirects',
            'logs404'
        ));
    }

    /**
     * Show redirect rule creation form.
     */
    public function create(Request $request): View
    {
        $rawSource = $request->query('source');
        $prefilledSource = is_string($rawSource) ? trim($rawSource) : '';

        return view('redirects::admin.create', compact('prefilledSource'));
    }

    /**
     * Store new redirect rule.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'source_path' => 'required|string|max:500',
            'target_url' => 'required|string|max:2000',
            'status_code' => 'required|in:301,302,307,308',
            'description' => 'nullable|string|max:255',
        ]);

        $rawSource = $request->input('source_path');
        $sourcePath = RedirectRule::normalizePath(is_string($rawSource) ? $rawSource : '');

        $rawTarget = $request->input('target_url');
        $targetUrl = is_string($rawTarget) ? trim($rawTarget) : '';

        // Prevent infinite loop
        if ($sourcePath === RedirectRule::normalizePath($targetUrl)) {
            return back()->withErrors(['target_url' => 'L\'URL cible ne peut pas être identique au chemin source (boucle infinie).'])->withInput();
        }

        $rawDesc = $request->input('description');
        $description = is_string($rawDesc) && trim($rawDesc) !== '' ? trim($rawDesc) : null;

        RedirectRule::create([
            'source_path' => $sourcePath,
            'target_url' => $targetUrl,
            'status_code' => (int) $request->input('status_code', 301),
            'is_active' => (bool) $request->boolean('is_active', true),
            'description' => $description,
        ]);

        // If this resolved a 404 log entry, clean it up
        NotFoundLog::query()->where('path', $sourcePath)->delete();

        return redirect('/admin/redirects')->with('success', 'Règle de redirection créée avec succès.');
    }

    /**
     * Show redirect edit form.
     */
    public function edit(int $id): View
    {
        /** @var RedirectRule $rule */
        $rule = RedirectRule::query()->findOrFail($id);

        return view('redirects::admin.edit', compact('rule'));
    }

    /**
     * Update redirect rule.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        /** @var RedirectRule $rule */
        $rule = RedirectRule::query()->findOrFail($id);

        $request->validate([
            'source_path' => 'required|string|max:500',
            'target_url' => 'required|string|max:2000',
            'status_code' => 'required|in:301,302,307,308',
            'description' => 'nullable|string|max:255',
        ]);

        $rawSource = $request->input('source_path');
        $sourcePath = RedirectRule::normalizePath(is_string($rawSource) ? $rawSource : '');

        $rawTarget = $request->input('target_url');
        $targetUrl = is_string($rawTarget) ? trim($rawTarget) : '';

        if ($sourcePath === RedirectRule::normalizePath($targetUrl)) {
            return back()->withErrors(['target_url' => 'L\'URL cible ne peut pas être identique au chemin source (boucle infinie).'])->withInput();
        }

        $rawDesc = $request->input('description');
        $description = is_string($rawDesc) && trim($rawDesc) !== '' ? trim($rawDesc) : null;

        $rule->update([
            'source_path' => $sourcePath,
            'target_url' => $targetUrl,
            'status_code' => (int) $request->input('status_code', 301),
            'is_active' => (bool) $request->boolean('is_active', true),
            'description' => $description,
        ]);

        return redirect('/admin/redirects')->with('success', 'Règle de redirection mise à jour avec succès.');
    }

    /**
     * Toggle redirection active status.
     */
    public function toggle(int $id): RedirectResponse
    {
        /** @var RedirectRule $rule */
        $rule = RedirectRule::query()->findOrFail($id);
        $rule->is_active = ! $rule->is_active;
        $rule->save();

        return redirect('/admin/redirects')->with('success', 'Statut de la redirection modifié.');
    }

    /**
     * Delete redirect rule.
     */
    public function destroy(int $id): RedirectResponse
    {
        /** @var RedirectRule $rule */
        $rule = RedirectRule::query()->findOrFail($id);
        $rule->delete();

        return redirect('/admin/redirects')->with('success', 'Règle de redirection supprimée avec succès.');
    }

    /**
     * Convert a detected 404 log entry into a redirect rule in one click.
     */
    public function convert404(Request $request, int $id): RedirectResponse
    {
        /** @var NotFoundLog $log */
        $log = NotFoundLog::query()->findOrFail($id);

        $request->validate([
            'target_url' => 'required|string|max:2000',
        ]);

        $rawTarget = $request->input('target_url');
        $targetUrl = is_string($rawTarget) ? trim($rawTarget) : '';

        RedirectRule::create([
            'source_path' => $log->path,
            'target_url' => $targetUrl,
            'status_code' => 301,
            'is_active' => true,
            'description' => 'Créé depuis le journal 404',
        ]);

        $log->delete();

        return redirect('/admin/redirects?tab=logs404')->with('success', 'Redirection 301 créée et erreur 404 résolue avec succès.');
    }

    /**
     * Delete a single 404 log entry.
     */
    public function destroy404(int $id): RedirectResponse
    {
        /** @var NotFoundLog $log */
        $log = NotFoundLog::query()->findOrFail($id);
        $log->delete();

        return redirect('/admin/redirects?tab=logs404')->with('success', 'Entrée 404 supprimée du journal.');
    }

    /**
     * Clear all 404 error logs.
     */
    public function clear404Logs(): RedirectResponse
    {
        NotFoundLog::query()->delete();

        return redirect('/admin/redirects?tab=logs404')->with('success', 'L\'ensemble du journal des erreurs 404 a été vidé.');
    }
}
