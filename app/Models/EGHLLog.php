<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EGHLLog extends Model
{
    use HasFactory;

    protected $table = 'eghl_log';
    protected $fillable = [
        'transaction_type',
        'payment_method',
        'service_id',
        'payment_id',
        'order_number',
        'payment_description',
        'amount',
        'currency_code',
        'hash',
        'txn_status',
        'txn_message',
        'response_hash',
        'issuing_bank',
        'bank_reference',
        'auth_code',
        'updated_at'
    ];
}
