<?php

use App\Models\Motor\Insurance;
use Carbon\Carbon;

if(!function_exists('formatDateFromIC')) {
    function formatDateFromIC(string $ic, string $format = 'Y-m-d') : string
    {
        $dob = explode('-', $ic)[0];
        $year = substr($dob, 0, 2);
        $month = substr($dob, 2, 2);
        $day = substr($dob, 4, 2);

        if ($year > Carbon::now()->format('y')) {
            $year += 1900;
        } else {
            $year += 2000;
        }

        return Carbon::parse(implode('-', [$year, $month, $day]))->format($format);
    }
}

if(!function_exists('getAgeFromIC')) {
    function getAgeFromIC($ic) : int
    {
        $dob = formatDateFromIC($ic);

        return Carbon::parse($dob)->diffInYears(Carbon::now());
    }
}

if(!function_exists('getGenderFromIC')) {
    function getGenderFromIC(string $ic) : string
    {
        if(substr($ic, -1, 1) % 2 === 0) {
            return 'F';
        } else {
            return 'M';
        }
    }
}

if(!function_exists('formatIC')) {
    function formatIC(string $ic, bool $with_dash = false) {
        $ic_number = str_replace("-", "", $ic);

        if($with_dash) {
            $tokens = str_split($ic_number, 6);
            $dob = $ic_number[0];
            $state_code = substr($tokens[1], 0, 2);
            $number = substr($tokens[1], 2, 2);

            $ic_number = implode('-', [$dob, $state_code, $number]);
        }

        return $ic_number;
    }
}

if(!function_exists('toObject')) {
    function toObject($data) {
        return json_decode(json_encode($data));
    }
}

if (!function_exists('formatNumber')) {
    function formatNumber($number, $decimals = 2)
    {
        // convert to string
        $number = str_replace(',', '', strval($number));

        return round(floatval($number), $decimals);
    }
}

if (!function_exists('generateExtraCoverSumInsured')) {
    function generateExtraCoverSumInsured(int $min, int $max, int $incremment = 1000)
    {
        $values = [];

        while ($min <= $max) {
            array_push($values, $min);

            $min = round($min + $incremment, ($incremment >= 1000 ? -3 : -2), PHP_ROUND_HALF_DOWN);
        }

        return $values;
    }
}

if (!function_exists('generateInsuranceCode')) {
    function generateInsuranceCode(string $abbreviation, string $company, string $insurance_id)
    {
        return $abbreviation . '/' . $company . '/' . $insurance_id;
    }
}

if(!function_exists('getInsuranceStatus')) {
    function getInsuranceStatus(int $status_id)
    {
        $status = '';

        switch($status_id) {
            case Insurance::STATUS_NEW_QUOTATION : {
                $status = 'Pending';
                break;
            }
            case Insurance::STATUS_POLICY_ISSUED: {
                $status = 'Policy Issued';
                break;
            }
            case Insurance::STATUS_POLICY_FAILURE: {
                $status = 'Policy Failed';
                break;
            }
            case Insurance::STATUS_CANCELLED: {
                $status = 'Cancelled';
                break;
            }
            case Insurance::STATUS_PAYMENT_ACCEPTED: {
                $status = 'Payment Accepted';
                break;
            }
            case Insurance::STATUS_PAYMENT_FAILURE: {
                $status = 'Payment Failed';
                break;
            }
        }

        return $status;
    }
}

if (!function_exists('roundSumInsured')) {
    function roundSumInsured(int $sum_insured, float $percentage, $round_down = false, int $extrema = 0)
    {
        $amount = $sum_insured * ($percentage / 100);

        if ($round_down) {
            $precision = ($sum_insured + $amount) / 1000;
            $sum_insured = 1000 * (floor($precision));

            if($extrema != 0 && $sum_insured > $extrema) {
                $sum_insured = $extrema;
            }
        } else {
            $precision = ($sum_insured - $amount) / 1000;
            $sum_insured = 1000 * (ceil($precision));

            if($extrema != 0 && $sum_insured < $extrema) {
                $sum_insured = $extrema;
            }
        }

        return $sum_insured;
    }
}

if (!function_exists('generatePaymentID')) {
    function generatePaymentID(int $attempt, string $insurance_code)
    {
        $attempt++;

        return $insurance_code . '-' . str_pad($attempt, 3, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('generateExtraCoverSumInsured')) {
    function generateExtraCoverSumInsured(int $min, int $max, int $incremment = 1000)
    {
        $values = [];

        while ($min <= $max) {
            array_push($values, $min);

            $min = round($min + $incremment, ($incremment >= 1000 ? -3 : -2), PHP_ROUND_HALF_DOWN);
        }

        return $values;
    }
}

if(!function_exists('formatAddress')) {
    function formatAddress(array $strings, int $length = 0)
    {
        $formatted_address = [];

        $address = '';
        foreach($strings as $string) {
            if(!empty($string)) {
                if($length > 0 && strlen(implode(' ', [$address, trim($string)])) > $length) {
                    array_push($formatted_address, $address);
                    $address = trim($string);
                } else {
                    $address .= trim($string) . ', ';
                }
            }
        }

        return $formatted_address;
    }
}

if (!function_exists('getPreVehicleNCD')) {
    function getPreVehicleNCD(float $ncd_percentage)
    {
        $ncd_percentage_list = [0, 25, 30, 38.33, 45, 55];

        $key = array_search($ncd_percentage, $ncd_percentage_list);

        return $ncd_percentage_list[$key - 1] ?? 0;
    }
}

