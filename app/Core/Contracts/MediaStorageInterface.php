<?php

declare(strict_types=1);

namespace App\Core\Contracts;

use Illuminate\Http\UploadedFile;

interface MediaStorageInterface
{
    /**
     * Store an uploaded file and return its persistent metadata.
     *
     * @return array{id: int|string, name: string, file_path: string, disk: string, mime_type: string, size: int, alt_text: ?string, url: string}
     */
    public function store(UploadedFile $file, ?string $directory = 'media'): array;

    /**
     * Delete a stored media file and its metadata by identifier or path.
     */
    public function delete(int|string $id): bool;

    /**
     * Get the absolute public web URL for a media item.
     */
    public function getUrl(string $path): string;

    /**
     * Determine whether a media file physically exists on storage.
     */
    public function exists(string $path): bool;

    /**
     * Find a media item's metadata by its ID or relative file path.
     *
     * @return array<string, mixed>|null
     */
    public function find(int|string $id): ?array;

    /**
     * Retrieve a list of media item metadata.
     *
     * @return list<array<string, mixed>>
     */
    public function all(int $limit = 50, int $offset = 0): array;
}
