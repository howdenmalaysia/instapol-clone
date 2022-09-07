<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceRemark extends Model
{
    use HasFactory;

    protected $fillable = [
        'insurance_id',
        'remarks',
        'updated_at'
    ];

    public function insurance()
    {
        return $this->belongsTo(Insurance::class);
    }
}
