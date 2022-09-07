<?php

namespace App\Models\Motor;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleBodyType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];
}
