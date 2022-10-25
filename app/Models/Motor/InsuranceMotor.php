<?php

namespace App\Models\Motor;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceMotor extends Model
{
    use HasFactory;

    protected $fillable = [
        'insurance_id',
        'vehicle_state_id',
        'vehicle_number',
        'chassis_number',
        'engine_number',
        'make',
        'model',
        'seating_capacity',
        'engine_capacity',
        'manufactured_year',
        'market_value',
        'nvic',
        'variant',
        'ncd_percentage',
        'ncd_amount',
        'previous_ncd_percentage',
        'next_ncd_percentage',
        'previous_inception_date',
        'previous_expiry_date',
        'previous_policy_expiry',
        'disabled',
        'marital_status',
        'driving_experience',
        'loading',
        'number_of_drivers',
        'created_by',
        'updated_by',
        'updated_at'
    ];

    public function insurance()
    {
        return $this->belongsTo(Insurance::class);
    }

    public function driver()
    {
        return $this->hasMany(InsuranceMotorDriver::class);
    }

    public function roadtax()
    {
        return $this->hasOne(InsuranceMotorRoadtax::class);
    }
}
