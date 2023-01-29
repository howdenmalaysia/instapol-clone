<?php

namespace App\Models\Motor;

use App\Scopes\Motor\ActiveScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceCompany extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'logo',
        'sequence',
        'active',
        'coming_soon',
        'bank_code',
        'bank_account_no',
        'email_to',
        'email_cc'
    ];

    public function __construct()
    {
        static::addGlobalScope(new ActiveScope);
    }

    public function product()
    {
        return $this->hasMany(Product::class);
    }
}
