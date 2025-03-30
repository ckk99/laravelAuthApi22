<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settlement extends Model
{
    use HasFactory;
    protected $fillable = [
        'mid', 'rid', 'amount', 'utr', 'paygicReferenceNumber',
        'bankReferenceNumber', 'mode', 'initiationDate', 'status'
    ];
}
