<?php

namespace App\Models\Motor;

use App\Scopes\Motor\ActiveScope;
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

    public function __construct()
    {
        static::addGlobalScope(new ActiveScope);
    }

    public function product_type()
    {
        return $this->belongsTo(ProductType::class);
    }

    public function insurance_company()
    {
        return $this->belongsTo(InsuranceCompany::class, 'company_id', 'id');
    }
}
