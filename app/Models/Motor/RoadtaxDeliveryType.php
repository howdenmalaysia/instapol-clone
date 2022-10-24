<?php

namespace App\Models\Motor;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoadtaxDeliveryType extends Model
{
    use HasFactory;

    public const WM = 'West Malaysia';
    public const EM = 'East Malaysia';
    public const KV = 'Klang Valley';
    public const OTHERS = 'Others';

    protected $fillable = [
        'description',
        'amount',
        'processing_fee'
    ];
}
