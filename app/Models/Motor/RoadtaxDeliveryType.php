<?php

namespace App\Models\Motor;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoadtaxDeliveryType extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'amount',
        'processing_fee'
    ];
}
