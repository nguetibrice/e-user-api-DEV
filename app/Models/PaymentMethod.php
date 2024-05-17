<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;
    use BaseModel;

    protected $fillable = [
        "country",
        "name",
        "image",
        "min_limit",
        "max_limit",
        "description",
        "status",
        "code",
    ];

    public function currencies()
    {
        return $this->belongsToMany(Currency::class, "payment_method_currencies");
    }
}
