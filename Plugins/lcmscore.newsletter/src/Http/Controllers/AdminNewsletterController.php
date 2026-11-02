<?php

declare(strict_types=1);

namespace Plugins\Newsletter\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;
use Plugins\Newsletter\Mail\ConfirmationMail;
use Plugins\Newsletter\Models\Subscriber;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminNewsletterController extends Controller
{
    /**
     * Display subscribers dashboard and list.
     */
    public function index(Request $request): View
    {
        $rawStatus = $request->query('status');
        $status = is_string($rawStatus) ? trim($rawStatus) : 'all';

        $rawSearch = $request->query('search');
        $search = is_string($rawSearch) ? trim($rawSearch) : '';

        // KPI Counts
        $totalCount = Subscriber::query()->count();
        $confirmedCount = Subscriber::query()->where('status', 'confirmed')->count();
        $pendingCount = Subscriber::query()->where('status', 'pending')->count();
        $unsubscribedCount = Subscriber::query()->where('status', 'unsubscribed')->count();

        $confirmationRate = $totalCount > 0
            ? (int) round(($confirmedCount / $totalCount) * 100)
            : 0;

        // Build query
        $query = Subscriber::query()->latest('id');

        if ($status !== 'all' && in_array($status, ['confirmed', 'pending', 'unsubscribed'], true)) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where('email', 'like', '%'.$search.'%');
        }

        $subscribers = $query->paginate(25)->withQueryString();

        return view('newsletter::admin.index', compact(
            'subscribers',
            'status',
            'search',
            'totalCount',
            'confirmedCount',
            'pendingCount',
            'unsubscribedCount',
            'confirmationRate'
        ));
    }

    /**
     * Manually add a subscriber from admin panel.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email|max:255|unique:plugin_newsletter_subscribers,email',
            'status' => 'required|in:confirmed,pending',
        ]);

        $rawEmail = $request->input('email');
        $email = is_string($rawEmail) ? mb_strtolower(trim($rawEmail)) : '';

        $rawStatus = $request->input('status');
        $status = is_string($rawStatus) ? trim($rawStatus) : 'confirmed';

        /** @var Subscriber $subscriber */
        $subscriber = Subscriber::create([
            'email' => $email,
            'status' => $status,
            'token' => Subscriber::generateToken(),
            'source' => 'admin',
            'confirmed_at' => $status === 'confirmed' ? now() : null,
        ]);

        if ($status === 'pending') {
            try {
                $confirmationUrl = url('/newsletter/confirm/'.$subscriber->token);
                Mail::to($subscriber->email)->send(new ConfirmationMail($subscriber, $confirmationUrl));
            } catch (\Throwable) {
                // Ignore mail delivery failure in admin store
            }
        }

        return redirect('/admin/newsletter')->with('success', 'Abonné ajouté avec succès.');
    }

    /**
     * Toggle status between confirmed and unsubscribed.
     */
    public function toggle(int $id): RedirectResponse
    {
        /** @var Subscriber $subscriber */
        $subscriber = Subscriber::query()->findOrFail($id);

        if ($subscriber->isConfirmed()) {
            $subscriber->unsubscribe();
        } else {
            $subscriber->confirm();
        }

        return redirect('/admin/newsletter')->with('success', 'Statut de l\'abonné mis à jour avec succès.');
    }

    /**
     * Resend double opt-in confirmation email.
     */
    public function resendConfirmation(int $id): RedirectResponse
    {
        /** @var Subscriber $subscriber */
        $subscriber = Subscriber::query()->findOrFail($id);

        try {
            $confirmationUrl = url('/newsletter/confirm/'.$subscriber->token);
            Mail::to($subscriber->email)->send(new ConfirmationMail($subscriber, $confirmationUrl));

            return redirect('/admin/newsletter')->with('success', 'E-mail de confirmation renvoyé avec succès à '.$subscriber->email.'.');
        } catch (\Throwable $e) {
            return redirect('/admin/newsletter')->with('error', 'Échec de l\'envoi de l\'e-mail : '.$e->getMessage());
        }
    }

    /**
     * Delete a subscriber.
     */
    public function destroy(int $id): RedirectResponse
    {
        /** @var Subscriber $subscriber */
        $subscriber = Subscriber::query()->findOrFail($id);
        $subscriber->delete();

        return redirect('/admin/newsletter')->with('success', 'Abonné supprimé avec succès.');
    }

    /**
     * Export subscribers list as CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $rawStatus = $request->query('status');
        $status = is_string($rawStatus) ? trim($rawStatus) : 'all';

        $query = Subscriber::query()->latest('id');

        if ($status !== 'all' && in_array($status, ['confirmed', 'pending', 'unsubscribed'], true)) {
            $query->where('status', $status);
        }

        $filename = 'newsletter_abonnes_'.now()->format('Y-m-d_His').'.csv';
        $headers = ['ID', 'E-mail', 'Statut', 'Source', 'Adresse IP', 'Date Inscription', 'Date Confirmation', 'Date Désabonnement'];

        return response()->streamDownload(function () use ($query, $headers): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM for Microsoft Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, $headers, ';');

            $query->chunk(100, function ($chunk) use ($handle): void {
                foreach ($chunk as $sub) {
                    /** @var Subscriber $sub */
                    fputcsv($handle, [
                        $sub->id,
                        $sub->email,
                        $sub->status,
                        $sub->source,
                        $sub->ip_address ?? '',
                        $sub->created_at ? $sub->created_at->format('Y-m-d H:i:s') : '',
                        $sub->confirmed_at ? $sub->confirmed_at->format('Y-m-d H:i:s') : '',
                        $sub->unsubscribed_at ? $sub->unsubscribed_at->format('Y-m-d H:i:s') : '',
                    ], ';');
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
