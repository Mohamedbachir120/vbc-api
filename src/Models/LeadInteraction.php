<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A logged touchpoint with a lead: a note, a call, a visit, or an email.
 *
 * @property int $id
 * @property int $leadId
 * @property int|null $userId
 * @property string $type
 * @property string $content
 */
class LeadInteraction extends Model
{
    protected $table = 'lead_interactions';

    public const TYPES = ['note', 'appel', 'visite', 'email'];

    public const UPDATED_AT = null;

    protected $fillable = [
        'leadId',
        'userId',
        'type',
        'content',
    ];

    protected $attributes = [
        'type' => 'note',
    ];

    public $timestamps = true;

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getLeadIdAttribute(): int
    {
        return (int) $this->attributes['lead_id'];
    }

    public function setLeadIdAttribute($value): void
    {
        $this->attributes['lead_id'] = (int) $value;
    }

    public function getUserIdAttribute(): ?int
    {
        return isset($this->attributes['user_id']) ? (int) $this->attributes['user_id'] : null;
    }

    public function setUserIdAttribute($value): void
    {
        $this->attributes['user_id'] = $value === null ? null : (int) $value;
    }

    public function toApiArray(): array
    {
        return [
            'id'        => $this->id,
            'leadId'    => $this->leadId,
            'type'      => $this->type,
            'content'   => $this->content,
            'author'    => $this->relationLoaded('user') && $this->user
                ? ['id' => $this->user->id, 'name' => $this->user->name]
                : null,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
