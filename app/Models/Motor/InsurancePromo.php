<?php

namespace App\Models\Motor;

use App\Models\Promotion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InsurancePromo extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'insurance_id',
        'promo_id',
        'discount_amount'
    ];

    public function insurance()
    {
        return $this->belongsTo(Insurance::class);
    }

    public function promotion()
    {
        return $this->belongsTo(Promotion::class, 'promo_id', 'id');
    }
}
