<?php

namespace App\Models\Motor;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InsuranceMotorDriver extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'insurance_motor_id',
        'name',
        'id_number',
        'updated_at'
    ];


    public function insurance_motor()
    {
        return $this->belongsTo(InsuranceMotor::class);
    }
}
