<?php

namespace App\Models\Motor;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    use HasFactory;

    public const INACTIVE = 0;
    public const ACTIVE = 1;

    protected $fillable = [
        'product_type',
        'vehicle_number',
        'email_address',
        'request_param',
        'referrer',
        'remarks',
        'active',
        'compare_page',
        'updated_at',
    ];

    public function insurance_motor()
    {
        return $this->hasOne(InsuranceMotor::class, 'vehicle_number', 'vehicle_number');
    }
}
