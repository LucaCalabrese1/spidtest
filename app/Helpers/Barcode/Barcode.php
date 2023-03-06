<?php

namespace App\Helpers\Barcode;


class Barcode
{
    public static function checkDigits($digits)
    {
        $even_sum = $digits[1] + $digits[3] + $digits[5] + $digits[7] + $digits[9] + $digits[11];
        $even_sum_three = $even_sum * 3;
        $odd_sum = $digits[0] + $digits[2] + $digits[4] + $digits[6] + $digits[8] + $digits[10];
        $total_sum = $even_sum_three + $odd_sum;
        $next_ten = (ceil($total_sum / 10)) * 10;
        $check_digit = $next_ten - $total_sum;

        return $check_digit;
    }

    public static function generateRand()
    {
       return mt_rand(100000000000, 999999999999);
    }

    public static function create()
    {
        $digits = self::generateRand();
        $paddedDigits = str_pad($digits, 12, '0', STR_PAD_LEFT);
        $number = (int)($paddedDigits . self::checkDigits($paddedDigits));

        if (self::barcodeNumberExists($number)) {
            return self::create();
        }

        return $number;
    }

    public static function barcodeNumberExists($number)
    {
        return whereBarcodeNumber($number);
    }
}
