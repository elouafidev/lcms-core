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
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<MediaItem> query()
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
     * @return array{id: int, name: string, file_path: string, disk: string, mime_type: string, size: int, alt_text: ?string, url: string}
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
            'alt_text' => $this->alt_text,
            'url' => $this->getResolvedUrl(),
        ];
    }
}
