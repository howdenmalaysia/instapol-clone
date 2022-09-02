<?php

namespace App\Models\Motor;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_type_id',
        'company_id',
        'name',
        'active',
    ];

    public function product_type()
    {
        return $this->belongsTo(ProductType::class);
    }

    public function insurance_company()
    {
        return $this->belongsTo(Company::class);
    }
}
