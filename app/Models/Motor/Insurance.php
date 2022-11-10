<?php

namespace App\Models\Motor;

use App\Models\InsuranceRemark;
use App\Models\Motor\InsuranceAddress;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Insurance extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'insurance_code',
        'customer_id',
        'insurance_status',
        'referrer',
        'inception_date',
        'expiry_date',
        'amount',
        'policy_number',
        'created_by',
        'updated_by',
        'quotation_date',
        'channel',
        'updated_at'
    ];

    const STATUS_NEW_QUOTATION = 1;
    const STATUS_POLICY_ISSUED = 2;
    const STATUS_CANCELLED = 3;
    const STATUS_POLICY_FAILURE = 4;
    const STATUS_PAYMENT_ACCEPTED = 5;
    const STATUS_PAYMENT_FAILURE = 6;

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function extra_attribute()
    {
        return $this->hasOne(InsuranceExtraAttribute::class);
    }

    public function extra_cover()
    {
        return $this->hasMany(InsuranceExtraCover::class);
    }

    public function holder()
    {
        return $this->hasOne(InsuranceHolder::class, 'insurance_id', 'id');
    }

    public function motor()
    {
        return $this->hasOne(InsuranceMotor::class, 'insurance_id', 'id');
    }
    
    public function address()
    {
        return $this->hasOne(InsuranceAddress::class);
    }

    public function promo()
    {
        return $this->hasOne(InsurancePromo::class);
    }

    public function remark()
    {
        return $this->hasMany(InsuranceRemark::class);
    }

    public static function findByInsuranceCode(string $insurance_code) : self
    {
        return self::with([
                'product',
                'extra_cover',
                'holder',
                'motor',
            ])
            ->where('insurance_code', $insurance_code)
            ->first();
    }
}
