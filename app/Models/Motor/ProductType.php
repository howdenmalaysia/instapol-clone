<?php

namespace App\Models\Motor;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductType extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'commission',
    ];

    public function product()
    {
        return $this->hasMany(Product::class);
    }
}
