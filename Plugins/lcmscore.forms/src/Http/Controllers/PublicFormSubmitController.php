<?php

declare(strict_types=1);

namespace Plugins\Forms\Http\Controllers;

use Core\Http\Controllers\Controller;
use Core\PluginRegistry\PluginRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Plugins\Forms\Models\Form;
use Plugins\Forms\Models\FormSubmission;
use Throwable;

class PublicFormSubmitController extends Controller
{
    public function __construct(
        private PluginRegistry $pluginRegistry
    ) {}

    public function submit(Request $request, string $slug): JsonResponse|RedirectResponse
    {
        if (! $this->pluginRegistry->isActive('lcmscore.forms')) {
            abort(404, 'Forms plugin is disabled.');
        }

        /** @var Form|null $form */
        $form = Form::query()->where('slug', $slug)->first();

        if ($form === null || ! $form->is_active) {
            abort(404, "Le formulaire '{$slug}' est introuvable ou inactif.");
        }

        // Honeypot anti-spam check
        if ($form->honeypot_enabled) {
            $honeypot = $request->input('_hp_website');
            if (! empty($honeypot)) {
                // Bot detected! Return fake success without persisting data
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => $form->success_message,
                    ]);
                }

                return redirect()->back()->with('form_success', $form->success_message)->with('submitted_form_slug', $form->slug);
            }
        }

        // Build dynamic validation rules from form's defined fields
        $rules = [];
        $fieldKeys = [];

        foreach ($form->fields as $field) {
            $name = $field['name'];
            $fieldKeys[] = $name;

            $fieldRules = [];
            $fieldRules[] = ! empty($field['required']) ? 'required' : 'nullable';

            switch ($field['type']) {
                case 'email':
                    $fieldRules[] = 'email';
                    $fieldRules[] = 'max:255';
                    break;
                case 'number':
                    $fieldRules[] = 'numeric';
                    break;
                case 'textarea':
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:5000';
                    break;
                case 'select':
                case 'radio':
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:255';
                    break;
                case 'checkbox':
                    $fieldRules[] = 'nullable';
                    break;
                default:
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:255';
                    break;
            }

            $rules[$name] = $fieldRules;
        }

        $validated = $request->validate($rules);

        // Filter and collect submitted data
        $submissionData = [];
        foreach ($fieldKeys as $key) {
            $submissionData[$key] = $validated[$key] ?? null;
        }

        /** @var FormSubmission $submission */
        $submission = FormSubmission::query()->create([
            'form_id' => $form->id,
            'data' => $submissionData,
            'ip_address' => $request->ip(),
            'user_agent' => is_string($request->userAgent()) ? substr($request->userAgent(), 0, 500) : null,
            'is_read' => false,
        ]);

        // Optional email notification to recipient
        if ($form->send_notification && ! empty($form->recipient_email)) {
            $this->sendNotificationEmail($form, $submission);
        }

        $successMsg = $form->success_message ?: __('Merci pour votre message. Nous vous répondrons dans les plus brefs délais.');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $successMsg,
                'submission_id' => $submission->id,
            ], 201);
        }

        return redirect()->back()
            ->with('form_success', $successMsg)
            ->with('submitted_form_slug', $form->slug);
    }

    private function sendNotificationEmail(Form $form, FormSubmission $submission): void
    {
        try {
            $recipient = $form->recipient_email;
            if ($recipient === null || $recipient === '') {
                return;
            }

            $subject = 'Nouvelle soumission : '.$form->name;
            $body = "Une nouvelle soumission a été reçue pour le formulaire \"{$form->name}\".\n\n";

            foreach ($submission->data as $k => $val) {
                $displayVal = is_array($val) ? implode(', ', $val) : (string) $val;
                $body .= ucfirst($k).': '.$displayVal."\n";
            }

            $body .= "\nReçue le : ".now()->format('d/m/Y H:i:s');
            $body .= "\nAdresse IP : ".($submission->ip_address ?? 'Inconnue');

            Mail::raw($body, function ($message) use ($recipient, $subject): void {
                $message->to($recipient)->subject($subject);
            });
        } catch (Throwable $e) {
            // Log warning without failing the user submission
            Log::warning("Form notification email failed for form '{$form->slug}': ".$e->getMessage());
        }
    }
}
