<?php

declare(strict_types=1);

namespace Plugins\I18n\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Plugins\I18n\Services\LanguageManager;

class PublicLanguageSwitchController extends Controller
{
    public function __construct(
        private LanguageManager $languageManager
    ) {}

    /**
     * Switch public active language and redirect back.
     */
    public function switch(Request $request, string $code): RedirectResponse
    {
        $this->languageManager->setLocale($code);

        $referer = $request->header('referer');
        if (is_string($referer) && trim($referer) !== '') {
            return redirect()->to($referer);
        }

        return redirect('/');
    }
}
