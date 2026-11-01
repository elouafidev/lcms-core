<?php

declare(strict_types=1);

namespace Plugins\MediaLibrary\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $name
 * @property string $file_path
 * @property string $disk
 * @property string $mime_type
 * @property int $size
 * @property string|null $alt_text
 * @property string|null $url
 * @property-read string $formatted_size
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<MediaItem> query()
 * @method static \Illuminate\Database\Eloquent\Builder<MediaItem> search(?string $term)
 * @method static \Illuminate\Database\Eloquent\Builder<MediaItem> ofType(?string $type)
 */
class MediaItem extends Model
{
    protected $table = 'plugin_media_items';

    protected $fillable = [
        'name',
        'file_path',
        'disk',
        'mime_type',
        'size',
        'alt_text',
        'url',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'size' => 'integer',
    ];

    /**
     * Determine if the media item is an image.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Get a human-readable file size representation.
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }

    /**
     * Scope query to search by name or alt text.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<MediaItem>  $query
     * @return \Illuminate\Database\Eloquent\Builder<MediaItem>
     */
    public function scopeSearch(\Illuminate\Database\Eloquent\Builder $query, ?string $term): \Illuminate\Database\Eloquent\Builder
    {
        if ($term === null || trim($term) === '') {
            return $query;
        }

        $clean = '%'.trim($term).'%';

        return $query->where(function (\Illuminate\Database\Eloquent\Builder $q) use ($clean): void {
            $q->where('name', 'LIKE', $clean)
                ->orWhere('alt_text', 'LIKE', $clean);
        });
    }

    /**
     * Scope query to filter by general type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<MediaItem>  $query
     * @return \Illuminate\Database\Eloquent\Builder<MediaItem>
     */
    public function scopeOfType(\Illuminate\Database\Eloquent\Builder $query, ?string $type): \Illuminate\Database\Eloquent\Builder
    {
        if ($type === null || $type === '' || $type === 'all') {
            return $query;
        }

        if ($type === 'image') {
            return $query->where('mime_type', 'LIKE', 'image/%');
        }

        if ($type === 'document') {
            return $query->where('mime_type', 'NOT LIKE', 'image/%');
        }

        return $query->where('mime_type', 'LIKE', '%'.$type.'%');
    }

    /**
     * Retrieve the public URL for the media asset.
     */
    public function getResolvedUrl(): string
    {
        if ($this->url !== null && $this->url !== '') {
            return $this->url;
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $storage */
        $storage = Storage::disk($this->disk);

        return $storage->url($this->file_path);
    }

    /**
     * Convert the model instance to a media metadata array.
     *
     * @return array{id: int, name: string, file_path: string, disk: string, mime_type: string, size: int, formatted_size: string, is_image: bool, alt_text: ?string, url: string}
     */
    public function toMetadataArray(): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'file_path' => $this->file_path,
            'disk' => $this->disk,
            'mime_type' => $this->mime_type,
            'size' => (int) $this->size,
            'formatted_size' => $this->formatted_size,
            'is_image' => $this->isImage(),
            'alt_text' => $this->alt_text,
            'url' => $this->getResolvedUrl(),
        ];
    }
}
