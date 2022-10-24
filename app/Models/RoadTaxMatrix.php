<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoadTaxMatrix extends Model
{
    use HasFactory;

    protected $table = 'roadtax_matrix';
    protected $fillable = [
        'saloon',
        'registration_type',
        'engine_capacity_from',
        'engine_capacity_to',
        'region',
        'base_rate',
        'progressive_rate'
    ];
}
