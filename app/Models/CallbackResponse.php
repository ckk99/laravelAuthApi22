<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallbackResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'contact_no',
        'user_unq_code',
        'user_upi_id',
        'user_unique_code',
        'amount',
        'date',
        'time',
        'utr',
        'mid',
        'vpa_id'
    ];
}
