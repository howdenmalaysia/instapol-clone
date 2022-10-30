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
        'myeg_fee',
        'e_service_fee',
        'service_tax',
        'issued',
        'tracking_code',
        'admin_charge',
        'success',
        'active',
        'recipient_name',
        'recipient_phone_number',
        'recipient_address_one',
        'recipient_address_two',
        'recipient_postcode',
        'recipient_city',
        'recipient_state',
        'updated_at'
    ];

    public function insurance_motor()
    {
        return $this->belongsTo(InsuranceMotor::class);
    }
}
