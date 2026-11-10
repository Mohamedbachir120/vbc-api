<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Ramsey\Uuid\Uuid;

class Card extends Model
{
    protected $table = 'cards';

    protected $fillable = [
        'name',
        'role',
        'company',
        'website',
        'email',
        'phone',
        'location',
        'accent',
        'accentLight',
        'isActive',
        'userId',
    ];

    // Map camelCase API/model attributes to snake_case DB columns.
    protected $attributes = [
        'accent'       => '#6366f1',
        'accent_light' => '#e0e7ff',
        'is_active'    => true,
    ];

    public $timestamps = true;

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Card $card) {
            if (empty($card->uuid)) {
                $card->uuid = Uuid::uuid4()->toString();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function views(): HasMany
    {
        return $this->hasMany(CardView::class, 'card_id');
    }

    // --- camelCase accessors/mutators bridging to snake_case DB columns ---

    public function getAccentLightAttribute(): string
    {
        return $this->attributes['accent_light'];
    }

    public function setAccentLightAttribute(string $value): void
    {
        $this->attributes['accent_light'] = $value;
    }

    public function getIsActiveAttribute(): bool
    {
        return (bool) $this->attributes['is_active'];
    }

    public function setIsActiveAttribute($value): void
    {
        $this->attributes['is_active'] = (bool) $value;
    }

    public function getUserIdAttribute(): int
    {
        return (int) $this->attributes['user_id'];
    }

    public function setUserIdAttribute($value): void
    {
        $this->attributes['user_id'] = $value;
    }

    /**
     * Shape this model exactly like the documented API response object,
     * including the computed publicUrl / qrUrl fields.
     */
    public function toApiArray(string $baseUrl): array
    {
        return [
            'id'           => $this->id,
            'uuid'         => $this->uuid,
            'name'         => $this->name,
            'role'         => $this->role,
            'company'      => $this->company,
            'website'      => $this->website,
            'email'        => $this->email,
            'phone'        => $this->phone,
            'location'     => $this->location,
            'accent'       => $this->accent,
            'accentLight'  => $this->accentLight,
            'isActive'     => $this->isActive,
            'publicUrl'    => rtrim($baseUrl, '/') . '/api/public/card/' . $this->uuid,
            'qrUrl'        => rtrim($baseUrl, '/') . '/api/public/card/' . $this->uuid . '/qr',
            'createdAt'    => $this->created_at?->toIso8601String(),
            'updatedAt'    => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Public-facing shape (no publicUrl/qrUrl, matches /api/public/card/:uuid)
     */
    public function toPublicArray(): array
    {
        return [
            'id'          => $this->id,
            'uuid'        => $this->uuid,
            'name'        => $this->name,
            'role'        => $this->role,
            'company'     => $this->company,
            'website'     => $this->website,
            'email'       => $this->email,
            'phone'       => $this->phone,
            'location'    => $this->location,
            'accent'      => $this->accent,
            'accentLight' => $this->accentLight,
            'isActive'    => $this->isActive,
            'createdAt'   => $this->created_at?->toIso8601String(),
            'updatedAt'   => $this->updated_at?->toIso8601String(),
        ];
    }
}
