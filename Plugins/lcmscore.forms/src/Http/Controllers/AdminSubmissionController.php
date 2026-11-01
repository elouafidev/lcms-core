<?php

declare(strict_types=1);

namespace Plugins\Forms\Http\Controllers;

use Core\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Plugins\Forms\Models\Form;
use Plugins\Forms\Models\FormSubmission;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminSubmissionController extends Controller
{
    public function index(Request $request, int|string $formId): View|JsonResponse
    {
        /** @var Form|null $form */
        $form = is_numeric($formId)
            ? Form::query()->find((int) $formId)
            : Form::query()->where('slug', (string) $formId)->first();

        if ($form === null) {
            abort(404, 'Formulaire introuvable.');
        }

        $filter = $request->query('filter');

        $query = $form->submissions()->latest('id');

        if ($filter === 'unread') {
            $query->where('is_read', false);
        } elseif ($filter === 'read') {
            $query->where('is_read', true);
        }

        $submissions = $query->paginate(25);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'form' => $form,
                'data' => $submissions->items(),
                'meta' => [
                    'current_page' => $submissions->currentPage(),
                    'last_page' => $submissions->lastPage(),
                    'total' => $submissions->total(),
                ],
            ]);
        }

        return view('forms::admin.submissions.index', [
            'form' => $form,
            'submissions' => $submissions,
            'filter' => is_string($filter) ? $filter : 'all',
        ]);
    }

    public function show(int $id): JsonResponse
    {
        /** @var FormSubmission|null $submission */
        $submission = FormSubmission::query()->with('form')->find($id);

        if ($submission === null) {
            return response()->json(['success' => false, 'message' => 'Soumission introuvable.'], 404);
        }

        // Mark as read automatically when inspected
        if (! $submission->is_read) {
            $submission->is_read = true;
            $submission->save();
        }

        return response()->json([
            'success' => true,
            'data' => $submission,
        ]);
    }

    public function markAsRead(int $id): JsonResponse|RedirectResponse
    {
        /** @var FormSubmission|null $submission */
        $submission = FormSubmission::query()->find($id);

        if ($submission !== null) {
            $submission->is_read = true;
            $submission->save();
        }

        return response()->json(['success' => true]);
    }

    public function destroy(int $id): JsonResponse|RedirectResponse
    {
        /** @var FormSubmission|null $submission */
        $submission = FormSubmission::query()->find($id);

        if ($submission === null) {
            return response()->json(['success' => false, 'message' => 'Soumission introuvable.'], 404);
        }

        $formId = $submission->form_id;
        $submission->delete();

        return redirect('/admin/forms/'.$formId.'/submissions')->with('success', __('Soumission supprimée avec succès.'));
    }

    public function exportCsv(int|string $formId): StreamedResponse
    {
        /** @var Form|null $form */
        $form = is_numeric($formId)
            ? Form::query()->find((int) $formId)
            : Form::query()->where('slug', (string) $formId)->first();

        if ($form === null) {
            abort(404, 'Formulaire introuvable.');
        }

        $filename = 'soumissions_'.$form->slug.'_'.now()->format('Y-m-d_His').'.csv';

        /** @var list<string> $fieldKeys */
        $fieldKeys = array_map(fn (array $f): string => (string) $f['name'], $form->fields);
        /** @var list<string> $fieldLabels */
        $fieldLabels = array_map(fn (array $f): string => (string) $f['label'], $form->fields);

        $headers = array_merge(['ID', 'Date', 'Adresse IP'], $fieldLabels);

        return response()->streamDownload(function () use ($form, $headers, $fieldKeys): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // Write UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, $headers, ';');

            $form->submissions()->latest('id')->chunk(100, function ($chunk) use ($handle, $fieldKeys): void {
                foreach ($chunk as $sub) {
                    $row = [
                        $sub->id,
                        $sub->created_at ? $sub->created_at->format('Y-m-d H:i:s') : '',
                        $sub->ip_address ?? '',
                    ];

                    foreach ($fieldKeys as $key) {
                        $val = $sub->data[$key] ?? '';
                        if (is_array($val)) {
                            $val = implode(', ', $val);
                        }
                        $row[] = (string) $val;
                    }

                    fputcsv($handle, $row, ';');
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
