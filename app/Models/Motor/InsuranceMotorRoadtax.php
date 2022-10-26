<?php

namespace App\Models\Motor;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceMotorRoadtax extends Model
{
    use HasFactory;

    protected $table = 'insurance_motor_roadtax';

    protected $fillable = [
        'insurance_motor_id',
        'roadtax_delivery_region_id',
        'roadtax_renewal_fee',
        'e_service_fee',
        'tax',
        'issued',
        'tracking_code',
        'admin_charge',
        'success',
        'active',
        'updated_at'
    ];

    public function insurance_motor()
    {
        return $this->belongsTo(InsuranceMotor::class);
    }
}
