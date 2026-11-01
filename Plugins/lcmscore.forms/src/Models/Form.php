<?php

declare(strict_types=1);

namespace Plugins\Forms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property array<int, array{name: string, label: string, type: string, required?: bool, placeholder?: string, options?: string|array<int, string>}> $fields
 * @property string $submit_label
 * @property string|null $success_message
 * @property string|null $recipient_email
 * @property bool $send_notification
 * @property bool $is_active
 * @property bool $honeypot_enabled
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, FormSubmission> $submissions
 * @property-read int|null $submissions_count
 * @property-read int|null $unread_submissions_count
 *
 * @method static Builder<Form> query()
 * @method static Builder<Form> active()
 */
class Form extends Model
{
    protected $table = 'plugin_forms';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'fields',
        'submit_label',
        'success_message',
        'recipient_email',
        'send_notification',
        'is_active',
        'honeypot_enabled',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'fields' => 'array',
        'send_notification' => 'boolean',
        'is_active' => 'boolean',
        'honeypot_enabled' => 'boolean',
    ];

    /**
     * Submissions received for this form.
     *
     * @return HasMany<FormSubmission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'form_id');
    }

    /**
     * Unread submissions for this form.
     *
     * @return HasMany<FormSubmission, $this>
     */
    public function unreadSubmissions(): HasMany
    {
        $relation = $this->hasMany(FormSubmission::class, 'form_id');
        $relation->getQuery()->where('is_read', false);

        return $relation;
    }

    /**
     * Scope query to only include active forms.
     *
     * @param  Builder<Form>  $query
     * @return Builder<Form>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
