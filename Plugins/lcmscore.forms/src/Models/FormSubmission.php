<?php

declare(strict_types=1);

namespace Plugins\Forms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $form_id
 * @property array<string, mixed> $data
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property bool $is_read
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Form|null $form
 *
 * @method static Builder<FormSubmission> query()
 */
class FormSubmission extends Model
{
    protected $table = 'plugin_form_submissions';

    protected $fillable = [
        'form_id',
        'data',
        'ip_address',
        'user_agent',
        'is_read',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'form_id' => 'integer',
        'data' => 'array',
        'is_read' => 'boolean',
    ];

    /**
     * Form to which this submission belongs.
     *
     * @return BelongsTo<Form, $this>
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class, 'form_id');
    }
}
