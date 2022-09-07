<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'insurance_id',
        'unit_no',
        'building_name',
        'address_one',
        'address_two',
        'city',
        'state',
        'postcode',
        'created_by',
        'updated_by',
        'updated_at'
    ];

    public function insurance()
    {
        return $this->belongsTo(Insurance::class);
    }
}
