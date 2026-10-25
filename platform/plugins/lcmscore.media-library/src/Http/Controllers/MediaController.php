<?php

declare(strict_types=1);

namespace Plugins\MediaLibrary\Http\Controllers;

use App\Core\Contracts\MediaStorageInterface;
use App\Http\Controllers\Controller;
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
        $items = $this->mediaStorage->all();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $items,
            ]);
        }

        return view('media-library::index', [
            'items' => $items,
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
