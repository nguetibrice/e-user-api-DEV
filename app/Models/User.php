<?php

namespace App\Models;

use App\Events\UserCreatedOrUpdated;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\NewAccessToken;

/**
 * @property int id
 * @property string first_name
 * @property string last_name
 * @property string cip
 * @property string alias
 * @property string phone
 * @property string password
 * @property date birthday
 * @property string avatar
 * @property Collection passwordResets
 * @property Collection subscriptions
 * @property Collection assignedSubscriptions
 * @property Collection subscriptionAssignments
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, Billable, SoftDeletes, BaseModel;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'alias',
        'email',
        'phone',
        'password',
        'cip',
        'birthday',
        'referrer',
        'avatar',
        'guardian',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'birthday' => 'date'
    ];

    protected $with = ['subscriptions', 'assignedSubscriptions'];

    /**
     * @inheritDoc
     */
    public function createToken(string $name, array $abilities = ['*']): NewAccessToken
    {
        /**
         * @var PersonalAccessToken $token
         */
        $token = $this->tokens()->create(
            [
                'name' => $name,
                'token' => hash('sha256', $plainTextToken = Str::random(40)),
                'abilities' => $abilities,
                'expired_at' => now()->addHours(3)
            ]
        );

        return new NewAccessToken($token, $token->getKey() . '|' . $plainTextToken);
    }

    public function passwordResets(): MorphMany
    {
        return $this->morphMany(PasswordReset::class, 'resetable');
    }

    public function createPin(): PasswordReset
    {
        /**
         * @var PasswordReset $passwordReset
         */
        $passwordReset = $this->passwordResets()->create(
            [
                'contact' => $this->getContactDetail()
            ]
        );

        return $passwordReset;
    }

    /**
     * Get all of the assigned subscriptions for the user
     */
    public function assignedSubscriptions(): BelongsToMany
    {
        return $this->belongsToMany(Subscription::class, 'subscription_assignments');
    }

    /**
     * Get all of the subscription assignments of the user
     */
    public function subscriptionAssignments()
    {
        return $this->hasManyThrough(
            SubscriptionAssignment::class,
            Subscription::class,
            'user_id',
            'subscription_id',
            $this->getKeyName(),
            'id'
        );
    }

    protected $dispatchesEvents  = [
        'created' => UserCreatedOrUpdated::class,
        'updated' => UserCreatedOrUpdated::class,
    ];
}
