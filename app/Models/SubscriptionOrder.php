<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionOrder extends Model
{
    use HasFactory, BaseModel;
    protected $fillable = [
        "user_id",
        "product_name",
        "quantity",
        "price_id",
        "currency",
        "description",
        "status",
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
