<?php

declare(strict_types=1);

namespace Plugins\MediaLibrary\Services;

use App\Core\Contracts\MediaStorageInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Plugins\MediaLibrary\Models\MediaItem;
use RuntimeException;

class MediaStorageService implements MediaStorageInterface
{
    public function __construct(
        private string $disk = 'public'
    ) {}

    /**
     * Store an uploaded file and persist its metadata.
     *
     * @return array{id: int|string, name: string, file_path: string, disk: string, mime_type: string, size: int, alt_text: ?string, url: string}
     */
    public function store(UploadedFile $file, ?string $directory = 'media'): array
    {
        $targetDir = trim($directory ?? 'media', '/');
        $storedPath = $file->store($targetDir, $this->disk);

        if ($storedPath === false) {
            throw new RuntimeException('Failed to store uploaded media file.');
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $storage */
        $storage = Storage::disk($this->disk);
        $resolvedUrl = $storage->url($storedPath);

        /** @var MediaItem $item */
        $item = MediaItem::query()->create([
            'name' => $file->getClientOriginalName(),
            'file_path' => $storedPath,
            'disk' => $this->disk,
            'mime_type' => (string) ($file->getMimeType() ?? 'application/octet-stream'),
            'size' => (int) ($file->getSize() ?: 0),
            'alt_text' => null,
            'url' => $resolvedUrl,
        ]);

        return $item->toMetadataArray();
    }

    public function delete(int|string $id): bool
    {
        /** @var MediaItem|null $item */
        $item = is_numeric($id)
            ? MediaItem::query()->find((int) $id)
            : MediaItem::query()->where('file_path', (string) $id)->first();

        if ($item === null) {
            return false;
        }

        if (Storage::disk($item->disk)->exists($item->file_path)) {
            Storage::disk($item->disk)->delete($item->file_path);
        }

        return (bool) $item->delete();
    }

    public function getUrl(string $path): string
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter $storage */
        $storage = Storage::disk($this->disk);

        return $storage->url($path);
    }

    public function exists(string $path): bool
    {
        return Storage::disk($this->disk)->exists($path);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int|string $id): ?array
    {
        /** @var MediaItem|null $item */
        $item = is_numeric($id)
            ? MediaItem::query()->find((int) $id)
            : MediaItem::query()->where('file_path', (string) $id)->first();

        return $item?->toMetadataArray();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function all(int $limit = 50, int $offset = 0): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, MediaItem> $records */
        $records = MediaItem::query()
            ->latest('id')
            ->skip($offset)
            ->take($limit)
            ->get();

        return array_values($records
            ->map(fn (MediaItem $item): array => $item->toMetadataArray())
            ->all());
    }
}
