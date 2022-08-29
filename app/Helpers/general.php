<?php

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