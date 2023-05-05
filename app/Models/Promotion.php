<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_type',
        'code',
        'description',
        'valid_from',
        'valid_to',
        'use_count',
        'use_max',
        'discount_amount',
        'discount_percentage',
        'minimum_spend',
        'discount_target',
        'allowed_domain',
        'restrict_domain',
    ];

    const DT_ROADTAX = 'road_tax';
    const DT_TOTALPAYABLE = 'total_payable';
    const DT_GROSS_PREMIUM = 'gross_premium';
}
