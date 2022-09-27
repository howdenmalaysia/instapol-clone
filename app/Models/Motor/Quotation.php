<?php

namespace App\Models\Motor;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_type',
        'email_address',
        'request_param',
        'referrer',
        'remarks',
        'active',
        'compare_page',
        'updated_at',
    ];
}
