<?php

declare(strict_types=1);

namespace Plugins\Forms\Http\Controllers;

use Core\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Plugins\Forms\Models\Form;

class AdminFormController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $search = $request->query('search');

        $query = Form::query()
            ->withCount(['submissions', 'unreadSubmissions'])
            ->latest('id');

        if (is_string($search) && trim($search) !== '') {
            $clean = '%'.trim($search).'%';
            $query->where(function ($q) use ($clean): void {
                $q->where('name', 'LIKE', $clean)
                    ->orWhere('slug', 'LIKE', $clean);
            });
        }

        $forms = $query->paginate(20);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $forms->items(),
                'meta' => [
                    'current_page' => $forms->currentPage(),
                    'last_page' => $forms->lastPage(),
                    'total' => $forms->total(),
                ],
            ]);
        }

        return view('forms::admin.index', [
            'forms' => $forms,
            'search' => is_string($search) ? $search : '',
        ]);
    }

    public function create(): View
    {
        $defaultFields = [
            [
                'name' => 'nom',
                'label' => 'Nom complet',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Votre nom et prénom',
            ],
            [
                'name' => 'email',
                'label' => 'Adresse email',
                'type' => 'email',
                'required' => true,
                'placeholder' => 'nom@exemple.com',
            ],
            [
                'name' => 'message',
                'label' => 'Message',
                'type' => 'textarea',
                'required' => true,
                'placeholder' => 'Écrivez votre message ici...',
            ],
        ];

        $form = new Form([
            'submit_label' => 'Envoyer',
            'success_message' => 'Merci pour votre message. Nous vous répondrons dès que possible.',
            'is_active' => true,
            'honeypot_enabled' => true,
            'send_notification' => false,
            'fields' => $defaultFields,
        ]);

        return view('forms::admin.form-builder', [
            'form' => $form,
            'isEdit' => false,
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        /** @var array{name: string, slug?: ?string, description?: ?string, fields: array<int, array{name: string, label: string, type: string, required?: bool, placeholder?: string, options?: string|array<int, string>}>, submit_label?: ?string, success_message?: ?string, recipient_email?: ?string, send_notification?: ?bool, is_active?: ?bool, honeypot_enabled?: ?bool} $validated */
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:plugin_forms,slug'],
            'description' => ['nullable', 'string'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.name' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_]+$/'],
            'fields.*.label' => ['required', 'string', 'max:255'],
            'fields.*.type' => ['required', 'string', 'in:text,email,tel,textarea,select,checkbox,radio,number'],
            'fields.*.required' => ['nullable', 'boolean'],
            'fields.*.placeholder' => ['nullable', 'string', 'max:255'],
            'fields.*.options' => ['nullable'],
            'submit_label' => ['nullable', 'string', 'max:100'],
            'success_message' => ['nullable', 'string', 'max:1000'],
            'recipient_email' => ['nullable', 'email', 'max:255'],
            'send_notification' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'honeypot_enabled' => ['nullable', 'boolean'],
        ]);

        $slugInput = $validated['slug'] ?? null;
        $slug = is_string($slugInput) && $slugInput !== ''
            ? Str::slug($slugInput)
            : Str::slug($validated['name']);

        // Normalize fields structure
        $fields = array_values(array_map(function (array $field): array {
            $options = $field['options'] ?? null;
            if (is_string($options) && trim($options) !== '') {
                $options = array_values(array_filter(array_map('trim', explode(',', $options))));
            }

            return [
                'name' => Str::slug($field['name'], '_'),
                'label' => trim($field['label']),
                'type' => $field['type'],
                'required' => ! empty($field['required']),
                'placeholder' => ! empty($field['placeholder']) ? trim($field['placeholder']) : null,
                'options' => is_array($options) ? $options : null,
            ];
        }, $validated['fields']));

        /** @var Form $form */
        $form = Form::query()->create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'fields' => $fields,
            'submit_label' => (! empty($validated['submit_label'])) ? $validated['submit_label'] : 'Envoyer',
            'success_message' => (! empty($validated['success_message'])) ? $validated['success_message'] : 'Merci pour votre message. Nous vous répondrons dès que possible.',
            'recipient_email' => $validated['recipient_email'] ?? null,
            'send_notification' => ! empty($validated['send_notification']),
            'is_active' => $request->has('is_active') ? (bool) $request->input('is_active') : true,
            'honeypot_enabled' => $request->has('honeypot_enabled') ? (bool) $request->input('honeypot_enabled') : true,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Form created successfully.',
                'data' => $form,
            ], 201);
        }

        return redirect('/admin/forms')->with('success', __('Formulaire créé avec succès.'));
    }

    public function edit(int|string $id): View
    {
        /** @var Form|null $form */
        $form = is_numeric($id)
            ? Form::query()->find((int) $id)
            : Form::query()->where('slug', (string) $id)->first();

        if ($form === null) {
            abort(404, 'Formulaire introuvable.');
        }

        return view('forms::admin.form-builder', [
            'form' => $form,
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, int|string $id): JsonResponse|RedirectResponse
    {
        /** @var Form|null $form */
        $form = is_numeric($id)
            ? Form::query()->find((int) $id)
            : Form::query()->where('slug', (string) $id)->first();

        if ($form === null) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Form not found.'], 404);
            }

            return redirect('/admin/forms')->withErrors(__('Formulaire introuvable.'));
        }

        /** @var array{name: string, slug?: ?string, description?: ?string, fields: array<int, array{name: string, label: string, type: string, required?: bool, placeholder?: string, options?: string|array<int, string>}>, submit_label?: ?string, success_message?: ?string, recipient_email?: ?string, send_notification?: ?bool, is_active?: ?bool, honeypot_enabled?: ?bool} $validated */
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:plugin_forms,slug,'.$form->id],
            'description' => ['nullable', 'string'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.name' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_]+$/'],
            'fields.*.label' => ['required', 'string', 'max:255'],
            'fields.*.type' => ['required', 'string', 'in:text,email,tel,textarea,select,checkbox,radio,number'],
            'fields.*.required' => ['nullable', 'boolean'],
            'fields.*.placeholder' => ['nullable', 'string', 'max:255'],
            'fields.*.options' => ['nullable'],
            'submit_label' => ['nullable', 'string', 'max:100'],
            'success_message' => ['nullable', 'string', 'max:1000'],
            'recipient_email' => ['nullable', 'email', 'max:255'],
            'send_notification' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'honeypot_enabled' => ['nullable', 'boolean'],
        ]);

        $slugInput = $validated['slug'] ?? null;
        $slug = is_string($slugInput) && $slugInput !== ''
            ? Str::slug($slugInput)
            : Str::slug($validated['name']);

        $fields = array_values(array_map(function (array $field): array {
            $options = $field['options'] ?? null;
            if (is_string($options) && trim($options) !== '') {
                $options = array_values(array_filter(array_map('trim', explode(',', $options))));
            }

            return [
                'name' => Str::slug($field['name'], '_'),
                'label' => trim($field['label']),
                'type' => $field['type'],
                'required' => ! empty($field['required']),
                'placeholder' => ! empty($field['placeholder']) ? trim($field['placeholder']) : null,
                'options' => is_array($options) ? $options : null,
            ];
        }, $validated['fields']));

        $form->fill([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'fields' => $fields,
            'submit_label' => (! empty($validated['submit_label'])) ? $validated['submit_label'] : 'Envoyer',
            'success_message' => (! empty($validated['success_message'])) ? $validated['success_message'] : 'Merci pour votre message. Nous vous répondrons dès que possible.',
            'recipient_email' => $validated['recipient_email'] ?? null,
            'send_notification' => $request->has('send_notification') ? (bool) $request->input('send_notification') : false,
            'is_active' => $request->has('is_active') ? (bool) $request->input('is_active') : false,
            'honeypot_enabled' => $request->has('honeypot_enabled') ? (bool) $request->input('honeypot_enabled') : false,
        ]);

        $form->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Form updated successfully.',
                'data' => $form,
            ]);
        }

        return redirect('/admin/forms')->with('success', __('Formulaire mis à jour avec succès.'));
    }

    public function destroy(Request $request, int|string $id): JsonResponse|RedirectResponse
    {
        /** @var Form|null $form */
        $form = is_numeric($id)
            ? Form::query()->find((int) $id)
            : Form::query()->where('slug', (string) $id)->first();

        if ($form === null) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Form not found.'], 404);
            }

            return redirect('/admin/forms')->withErrors(__('Formulaire introuvable.'));
        }

        $form->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Form deleted successfully.',
            ]);
        }

        return redirect('/admin/forms')->with('success', __('Formulaire supprimé avec succès.'));
    }
}
