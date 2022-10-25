<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsurancePremium extends Model
{
    use HasFactory;

    protected $table = 'insurance_premium';

    protected $fillable = [
        'insurance_id',
        'basic_premium',
        'gross_premium',
        'act_premium',
        'net_premium',
        'service_tax_amount',
        'stamp_duty',
        'total_premium',
        'remarks'
    ];

    public function insurance()
    {
        return $this->belongsTo(Insurance::class);
    }
}
