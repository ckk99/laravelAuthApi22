<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDetail extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'bname',
        'lname',
        'phone',
        'mcc',
        'type',
        'city',
        'district',
        'stateCode',
        'pincode',
        'bpan',
        'gst',
        'account',
        'ifsc',
        'address1',
        'address2',
        'cin',
        'msme',
        'dob',
        'doi',
        'url'
    ];
}
