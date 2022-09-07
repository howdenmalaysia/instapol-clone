<?php

namespace App\Models\Motor;

use App\Models\IDType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceHolder extends Model
{
    use HasFactory;

    protected $fillable = [
        'insurance_id',
        'name',
        'id_type_id',
        'id_number',
        'nationality',
        'date_of_birth',
        'age',
        'gender',
        'phone_code',
        'phone_number',
        'email_address',
        'occupation',
        'created_by',
        'updated_by',
        'updated_at'
    ];

    public function insurance()
    {
        return $this->belongsTo(Insurance::class);
    }

    public function id_type()
    {
        return $this->hasOne(IDType::class, 'id_type_id', 'id');
    }
}
