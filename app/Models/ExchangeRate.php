<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;




class ExchangeRate extends Model
{
    use HasFactory;
    use BaseModel;

    protected $fillable = [
        "currency_id",
        "rate"
    ];
}
