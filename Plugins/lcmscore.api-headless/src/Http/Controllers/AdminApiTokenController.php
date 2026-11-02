<?php

declare(strict_types=1);

namespace Plugins\ApiHeadless\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Plugins\ApiHeadless\Models\ApiToken;

class AdminApiTokenController extends Controller
{
    /**
     * Display API Tokens management page and documentation.
     */
    public function index(Request $request): View
    {
        $rawTab = $request->query('tab');
        $tab = is_string($rawTab) && in_array($rawTab, ['tokens', 'docs'], true) ? $rawTab : 'tokens';

        $totalTokens = ApiToken::query()->count();
        $activeTokens = ApiToken::query()
            ->where(function ($q): void {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->count();
        $expiredTokens = ApiToken::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->count();

        $tokens = ApiToken::query()->latest('id')->paginate(20);

        return view('api-headless::admin.index', compact(
            'tab',
            'tokens',
            'totalTokens',
            'activeTokens',
            'expiredTokens'
        ));
    }

    /**
     * Create and store new API Token.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'days_valid' => 'nullable|integer|min:1|max:3650',
            'abilities' => 'nullable|array',
            'abilities.*' => 'string|max:50',
        ]);

        $rawName = $request->input('name');
        $name = is_string($rawName) ? trim($rawName) : 'Jeton API';

        $rawDays = $request->input('days_valid');
        $daysValid = is_numeric($rawDays) ? (int) $rawDays : null;

        $rawAbilities = $request->input('abilities');
        /** @var list<string> $abilities */
        $abilities = is_array($rawAbilities) && ! empty($rawAbilities)
            ? array_values(array_filter($rawAbilities, 'is_string'))
            : ['*'];

        $result = ApiToken::generate($name, $abilities, $daysValid);

        return redirect('/admin/api-tokens')
            ->with('success', 'Jeton API généré avec succès.')
            ->with('new_plain_token', $result['plainTextToken'])
            ->with('new_token_name', $result['token']->name);
    }

    /**
     * Revoke / delete API Token.
     */
    public function destroy(int $id): RedirectResponse
    {
        /** @var ApiToken $token */
        $token = ApiToken::query()->findOrFail($id);
        $token->delete();

        return redirect('/admin/api-tokens')->with('success', 'Jeton API révoqué avec succès.');
    }
}
