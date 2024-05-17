<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentSession extends Model
{
    use HasFactory, BaseModel;

    protected $fillable = [
        "reference",
        "order_id",
        "price_id",
        "order_type",
        "payment_method",
        "payment_url",
        "payment_token",
        "notification_token",
        "status",
        "amount",
        "currency",
    ];
}
