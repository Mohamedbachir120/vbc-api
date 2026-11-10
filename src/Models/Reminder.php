<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A manual follow-up reminder tied to a lead, owned by one commercial.
 *
 * @property int $id
 * @property int $leadId
 * @property int $userId
 * @property string $dueAt
 * @property string|null $note
 * @property bool $isDone
 */
class Reminder extends Model
{
    protected $table = 'reminders';

    public const UPDATED_AT = null;

    protected $fillable = [
        'leadId',
        'userId',
        'dueAt',
        'note',
        'isDone',
    ];

    protected $attributes = [
        'is_done' => false,
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

    public function getUserIdAttribute(): int
    {
        return (int) $this->attributes['user_id'];
    }

    public function setUserIdAttribute($value): void
    {
        $this->attributes['user_id'] = (int) $value;
    }

    public function getDueAtAttribute(): ?string
    {
        return $this->attributes['due_at'] ?? null;
    }

    public function setDueAtAttribute($value): void
    {
        $this->attributes['due_at'] = $value;
    }

    public function getIsDoneAttribute(): bool
    {
        return (bool) ($this->attributes['is_done'] ?? false);
    }

    public function setIsDoneAttribute($value): void
    {
        $this->attributes['is_done'] = (bool) $value;
    }

    public function toApiArray(): array
    {
        return [
            'id'        => $this->id,
            'leadId'    => $this->leadId,
            'lead'      => $this->relationLoaded('lead') && $this->lead
                ? ['id' => $this->lead->id, 'name' => $this->lead->name]
                : null,
            'dueAt'     => $this->dueAt,
            'note'      => $this->note,
            'isDone'    => $this->isDone,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
