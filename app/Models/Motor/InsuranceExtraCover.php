<?php

namespace App\Models\Motor;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InsuranceExtraCover extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'insurance_id',
        'insurance_extra_cover_type_id',
        'code',
        'description',
        'sum_insured',
        'cart_day',
        'cart_amount',
        'amount',
        'updated_at'
    ];

    public function insurance()
    {
        return $this->belongsTo(Insurance::class);
    }
}
