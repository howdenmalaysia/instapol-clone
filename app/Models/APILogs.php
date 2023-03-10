<?php

namespace App\Models;

use App\Models\Motor\InsuranceCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class APILogs extends Model
{
    use HasFactory;

    protected $fillable = [
        'insurance_company_id',
        'method',
        'domain',
        'path',
        'request_header',
        'request',
        'encrypted_request',
        'response_header',
        'response',
        'encrypted_response'
    ];

    protected $table = 'api_logs';

    public function insurer()
    {
        return $this->belongsTo(InsuranceCompany::class);
    }
}
