<?php

namespace App\Models\Motor;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceExtraAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'insurance_id',
        'value',
        'updated_at'
    ];
}
