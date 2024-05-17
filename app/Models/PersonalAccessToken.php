<?php

namespace App\Models;

use Carbon\Traits\Comparison;
use Laravel\Sanctum\PersonalAccessToken as Model;

/**
 * @property array abilities
 * @property ?Comparison expired_at
 * @method   static find($id, $columns = ['*'])
 * @method   static where($column, $operator = null, $value = null, string $boolean = 'and')
 */
class PersonalAccessToken extends Model
{
    protected $casts = [
        'abilities' => 'json',
        'last_used_at' => 'datetime',
        'expired_at' => 'datetime'
    ];

    protected $fillable = [
        'name',
        'token',
        'abilities',
        'expired_at'
    ];

    /**
     * Find the token instance matching the given token.
     *
     * @param  string $token
     * @return static|null
     */
    public static function findToken($token): ?PersonalAccessToken
    {
        if (strpos($token, '|') === false) {
            return static::where('token', hash('sha256', $token))->first();
        }

        [$id, $token] = explode('|', $token, 2);
        $instance = static::find($id);
        $isTokenMatching = hash_equals(
            $instance->token ?? 'token_not_found',
            hash('sha256', $token)
        );

        if ($isTokenMatching) {
            return $instance;
        }

        return null;
    }
}
