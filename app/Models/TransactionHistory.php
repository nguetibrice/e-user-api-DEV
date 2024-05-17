<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        "type",
        "amount",
        "user_id",
        "currency",
        "motif",
        "transaction_id",
        "rate",
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
