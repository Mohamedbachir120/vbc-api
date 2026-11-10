<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $email
 * @property string $password
 * @property string $name
 */
class User extends Model
{
    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    // Never leak the password hash in API responses.
    protected $hidden = [
        'password',
    ];

    public $timestamps = true;

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class, 'user_id');
    }
}
