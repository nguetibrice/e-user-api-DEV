<?php

namespace App\Models;

use App\Events\SubscriptionAssigned;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubscriptionAssignment extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $with = ['user'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'subscription_id',
        'user_id'
    ];

    protected $dispatchesEvents  = [
        "created" => SubscriptionAssigned::class
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
