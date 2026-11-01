<?php

declare(strict_types=1);

namespace Plugins\MediaLibrary\Http\Controllers;

use Core\Contracts\MediaStorageInterface;
use Core\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Plugins\MediaLibrary\Models\MediaItem;

class MediaController extends Controller
{
    public function __construct(
        private MediaStorageInterface $mediaStorage
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $search = $request->query('search');
        $type = $request->query('type');
        $perPageRaw = $request->query('per_page');
        $perPage = is_numeric($perPageRaw) ? (int) $perPageRaw : 24;

        $query = MediaItem::query()->latest('id');

        if (is_string($search) && trim($search) !== '') {
            $clean = '%'.trim($search).'%';
            $query->where(function (\Illuminate\Database\Eloquent\Builder $q) use ($clean): void {
                $q->where('name', 'LIKE', $clean)
                    ->orWhere('alt_text', 'LIKE', $clean);
            });
        }

        if (is_string($type) && $type !== '' && $type !== 'all') {
            if ($type === 'image') {
                $query->where('mime_type', 'LIKE', 'image/%');
            } elseif ($type === 'document') {
                $query->where('mime_type', 'NOT LIKE', 'image/%');
            } else {
                $query->where('mime_type', 'LIKE', '%'.$type.'%');
            }
        }

        if ($request->wantsJson()) {
            $paginated = $query->paginate($perPage > 0 ? $perPage : 24);
            $data = array_values($paginated->getCollection()->map(fn (MediaItem $item): array => $item->toMetadataArray())->all());

            return response()->json([
                'success' => true,
                'data' => $data,
                'meta' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                    'per_page' => $paginated->perPage(),
                    'total' => $paginated->total(),
                ],
            ]);
        }

        $items = $query->paginate($perPage > 0 ? $perPage : 24);

        return view('media-library::index', [
            'items' => $items,
            'search' => is_string($search) ? $search : '',
            'type' => is_string($type) ? $type : 'all',
        ]);
    }

    public function upload(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB
                'mimes:jpg,jpeg,png,gif,webp,svg,pdf',
            ],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var UploadedFile $file */
        $file = $validated['file'];
        $metadata = $this->mediaStorage->store($file, 'media');

        if (isset($validated['alt_text']) && is_string($validated['alt_text'])) {
            MediaItem::query()->where('id', $metadata['id'])->update([
                'alt_text' => $validated['alt_text'],
            ]);
            $metadata['alt_text'] = $validated['alt_text'];
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully.',
                'data' => $metadata,
            ], 201);
        }

        return redirect()->back()->with('success', __('File uploaded successfully.'));
    }

    public function show(int|string $id): JsonResponse
    {
        $metadata = $this->mediaStorage->find($id);

        if ($metadata === null) {
            return response()->json([
                'success' => false,
                'message' => 'Media item not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $metadata,
        ]);
    }

    public function update(Request $request, int|string $id): JsonResponse|RedirectResponse
    {
        /** @var MediaItem|null $item */
        $item = is_numeric($id)
            ? MediaItem::query()->find((int) $id)
            : MediaItem::query()->where('file_path', (string) $id)->first();

        if ($item === null) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Media item not found.',
                ], 404);
            }

            return redirect()->back()->withErrors(__('Media item not found.'));
        }

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        if (isset($validated['name']) && is_string($validated['name']) && trim($validated['name']) !== '') {
            $item->name = trim($validated['name']);
        }

        if (array_key_exists('alt_text', $validated)) {
            $item->alt_text = is_string($validated['alt_text']) && trim($validated['alt_text']) !== ''
                ? trim($validated['alt_text'])
                : null;
        }

        $item->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Media metadata updated successfully.',
                'data' => $item->toMetadataArray(),
            ]);
        }

        return redirect()->back()->with('success', __('Media item updated successfully.'));
    }

    public function destroy(Request $request, int|string $id): JsonResponse|RedirectResponse
    {
        $deleted = $this->mediaStorage->delete($id);

        if (! $deleted) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Media item not found or could not be deleted.',
                ], 404);
            }

            return redirect()->back()->withErrors(__('Media item not found or could not be deleted.'));
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Media item deleted successfully.',
            ]);
        }

        return redirect()->back()->with('success', __('Media item deleted successfully.'));
    }
}
