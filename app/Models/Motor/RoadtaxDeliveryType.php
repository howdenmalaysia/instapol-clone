<?php

namespace App\Models\Motor;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoadtaxDeliveryType extends Model
{
    use HasFactory;

    private const WM = 'West Malaysia';
    private const EM = 'East Malaysia';
    private const KV = 'Klang Valley';
    private const OTHERS = 'Others';

    protected $fillable = [
        'description',
        'amount',
        'processing_fee'
    ];
}
