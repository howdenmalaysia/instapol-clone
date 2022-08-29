<?php

namespace App\Models\Motor;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_type_id',
        'companny_id',
        'name',
        'active',
    ];

    public function product_type()
    {
        return $this->belongsTo(ProductType::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
