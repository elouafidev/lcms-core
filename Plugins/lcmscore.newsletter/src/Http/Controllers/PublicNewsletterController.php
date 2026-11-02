<?php

declare(strict_types=1);

namespace Plugins\Newsletter\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Plugins\Newsletter\Mail\ConfirmationMail;
use Plugins\Newsletter\Models\Subscriber;

class PublicNewsletterController extends Controller
{
    /**
     * Handle public subscription request with Double Opt-In.
     */
    public function subscribe(Request $request): JsonResponse|RedirectResponse
    {
        // 1. Anti-spam Honeypot check
        if (! empty($request->input('_hp_email'))) {
            return $request->wantsJson()
                ? response()->json(['success' => true, 'message' => 'Merci pour votre inscription.'])
                : back()->with('newsletter_success', 'Merci pour votre inscription.');
        }

        // 2. Validate request
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $rawEmail = $request->input('email');
        $email = is_string($rawEmail) ? mb_strtolower(trim($rawEmail)) : '';

        /** @var Subscriber|null $subscriber */
        $subscriber = Subscriber::query()->where('email', $email)->first();

        if ($subscriber !== null && $subscriber->isConfirmed()) {
            $msg = 'Vous êtes déjà inscrit(e) à notre lettre d\'information.';

            return $request->wantsJson()
                ? response()->json(['success' => true, 'message' => $msg])
                : back()->with('newsletter_success', $msg);
        }

        if ($subscriber === null) {
            $rawSource = $request->input('source', 'web');
            $source = is_string($rawSource) ? mb_substr(trim($rawSource), 0, 50) : 'web';

            /** @var Subscriber $subscriber */
            $subscriber = Subscriber::create([
                'email' => $email,
                'status' => 'pending',
                'token' => Subscriber::generateToken(),
                'source' => $source,
                'ip_address' => $request->ip(),
            ]);
        } elseif ($subscriber->isUnsubscribed()) {
            $subscriber->status = 'pending';
            $subscriber->token = Subscriber::generateToken();
            $subscriber->unsubscribed_at = null;
            $subscriber->save();
        }

        // Dispatch confirmation email for Double Opt-in
        try {
            $confirmationUrl = url('/newsletter/confirm/'.$subscriber->token);
            Mail::to($subscriber->email)->send(new ConfirmationMail($subscriber, $confirmationUrl));
        } catch (\Throwable $e) {
            Log::warning('Newsletter confirmation mail delivery failed: '.$e->getMessage());
        }

        $successMessage = 'Merci pour votre inscription ! Un e-mail de confirmation vient de vous être envoyé.';

        return $request->wantsJson()
            ? response()->json(['success' => true, 'message' => $successMessage])
            : back()->with('newsletter_success', $successMessage);
    }

    /**
     * Confirm subscription via Double Opt-in security token.
     */
    public function confirm(string $token): View
    {
        /** @var Subscriber|null $subscriber */
        $subscriber = Subscriber::query()->where('token', $token)->first();

        if ($subscriber === null) {
            abort(404, 'Lien de confirmation invalide ou introuvable.');
        }

        $subscriber->confirm();

        return view('newsletter::public.confirmed', compact('subscriber'));
    }

    /**
     * Unsubscribe recipient in one click via unsubscribe token.
     */
    public function unsubscribe(string $token): View
    {
        /** @var Subscriber|null $subscriber */
        $subscriber = Subscriber::query()->where('token', $token)->first();

        if ($subscriber === null) {
            abort(404, 'Lien de désabonnement invalide ou introuvable.');
        }

        $subscriber->unsubscribe();

        return view('newsletter::public.unsubscribed', compact('subscriber'));
    }
}
