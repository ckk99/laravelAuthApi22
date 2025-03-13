<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_type',
        'rid',
        'mid',
        'saralPeID',
        'payer_name',
        'payee_upi',
        'customer_name',
        'customer_email',
        'customer_mobile',
        'amount',
        'paygicReferenceId',
        'merchantReferenceId',
        'status',
        'payment_mode',
        'utr',
        'success_date',
    ];
}
