<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A prospect/client in the sales pipeline.
 *
 * @property int $id
 * @property string $name
 * @property string|null $phone
 * @property string|null $email
 * @property int|null $budgetMin
 * @property int|null $budgetMax
 * @property string|null $location
 * @property string|null $propertyType
 * @property array|null $options
 * @property string|null $message
 * @property string $source
 * @property string|null $campaign
 * @property string $status
 * @property int|null $assignedTo
 */
class Lead extends Model
{
    protected $table = 'leads';

    public const STATUSES = ['prospect', 'interesse', 'reserve', 'vendu'];

    protected $fillable = [
        'name',
        'phone',
        'email',
        'budgetMin',
        'budgetMax',
        'location',
        'propertyType',
        'options',
        'message',
        'source',
        'campaign',
        'status',
        'assignedTo',
    ];

    protected $attributes = [
        'source' => 'manual',
        'status' => 'prospect',
    ];

    public $timestamps = true;

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(LeadInteraction::class, 'lead_id')->orderByDesc('created_at');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class, 'lead_id')->orderBy('due_at');
    }

    // --- camelCase <-> snake_case bridges ---

    public function getBudgetMinAttribute(): ?int
    {
        return isset($this->attributes['budget_min']) ? (int) $this->attributes['budget_min'] : null;
    }

    public function setBudgetMinAttribute($value): void
    {
        $this->attributes['budget_min'] = $value === null || $value === '' ? null : (int) $value;
    }

    public function getBudgetMaxAttribute(): ?int
    {
        return isset($this->attributes['budget_max']) ? (int) $this->attributes['budget_max'] : null;
    }

    public function setBudgetMaxAttribute($value): void
    {
        $this->attributes['budget_max'] = $value === null || $value === '' ? null : (int) $value;
    }

    public function getPropertyTypeAttribute(): ?string
    {
        return $this->attributes['property_type'] ?? null;
    }

    public function setPropertyTypeAttribute($value): void
    {
        $this->attributes['property_type'] = $value;
    }

    public function getOptionsAttribute(): ?array
    {
        $raw = $this->attributes['options_json'] ?? null;

        return $raw ? json_decode($raw, true) : null;
    }

    public function setOptionsAttribute($value): void
    {
        $this->attributes['options_json'] = $value === null ? null : json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public function getAssignedToAttribute(): ?int
    {
        return isset($this->attributes['assigned_to']) ? (int) $this->attributes['assigned_to'] : null;
    }

    public function setAssignedToAttribute($value): void
    {
        $this->attributes['assigned_to'] = $value === null || $value === '' ? null : (int) $value;
    }

    public function toApiArray(): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'phone'        => $this->phone,
            'email'        => $this->email,
            'budgetMin'    => $this->budgetMin,
            'budgetMax'    => $this->budgetMax,
            'location'     => $this->location,
            'propertyType' => $this->propertyType,
            'options'      => $this->options,
            'message'      => $this->message,
            'source'       => $this->source,
            'campaign'     => $this->campaign,
            'status'       => $this->status,
            'assignedTo'   => $this->assignedTo,
            'assignee'     => $this->relationLoaded('assignee') && $this->assignee
                ? ['id' => $this->assignee->id, 'name' => $this->assignee->name]
                : null,
            'createdAt'    => $this->created_at?->toIso8601String(),
            'updatedAt'    => $this->updated_at?->toIso8601String(),
        ];
    }
}
