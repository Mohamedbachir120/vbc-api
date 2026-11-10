<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardView extends Model
{
    protected $table = 'card_views';

    // This table only has viewed_at (DB default CURRENT_TIMESTAMP) -
    // there's no created_at/updated_at pair, so disable Eloquent's default timestamps.
    public $timestamps = false;

    protected $fillable = [
        'ip',
        'userAgent',
        'cardId',
    ];

    protected $dates = ['viewed_at'];

    public function getUserAgentAttribute()
    {
        return $this->attributes['user_agent'] ?? null;
    }

    public function setUserAgentAttribute($value): void
    {
        $this->attributes['user_agent'] = $value;
    }

    public function setCardIdAttribute($value): void
    {
        $this->attributes['card_id'] = $value;
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class, 'card_id');
    }
}
