<?php

namespace App\Helpers;

use Carbon\Carbon;
use App\Helpers\CodiceFiscale\Checker;
use Illuminate\Support\Str;

class DataHelper
{
    public static function limitText($text, $limit)
    {
        return Str::limit($text, $limit);
    }

    /**
     * Format date to dd/mm/Y
     *
     * @return string
     */
    public static function dateFormat($dateToConvert)
    {
        return ($dateToConvert !== null) ? Carbon::parse($dateToConvert)->format('d/m/Y') : '';
    }

    public static function dateFormatCalendar($dateToConvert)
    {
        return ($dateToConvert !== null) ? Carbon::parse($dateToConvert)->format('Y-m-d') : '';
    }

    /**
     * Formaty the date for mysql Insert
     */
    public static function dateFormatMysql($dateToConvert)
    {
        $date = Carbon::createFromFormat('d/m/Y', $dateToConvert);
        return (Carbon::parse($date))->format('Y-m-d');
    }

    /**
     * Fomat Houre HH:MM => 24
     *
     * @return string
     */
    public static function hourFormat($hour)
    {
        return (Carbon::parse($hour))->format('G:i');
    }

    /**
     * Formate Price
     *
     * @param Float $price
     * @return String course.LBL_FREE OR '€ '.$price
     */
    public static function priceFormat($price)
    {
        $price =  number_format($price, 2);
        return $price;
    }

    /**
     * UpperCase text Mutator
     *
     * @param String $text
     * @return String $text Uppercase
     */
    public static function upperCase($text)
    {
        return strtoupper($text);
    }

    /**
     * Check Fical Code Formaly Valide
     *
     * @param String $fiscalCode
     * @return Bool
     */
    public static function fiscalCodeValidateFormaly($fiscalCode, $lastname, $firstname)
    {
        $fiscalCodeChecker = (New Checker)->isFormallyCorrect($fiscalCode, $lastname, $firstname);
        return $fiscalCodeChecker;
    }

    public static function fiscalCodeValidateEditProfile($fiscalCode, $sex, $birthday)
    {
        $fiscalCodeChecker = (New Checker)->checkFiscalCodeEditProfile($fiscalCode, $sex, $birthday);
        return $fiscalCodeChecker;
    }

    public static function isElearning($isElearning)
    {
        return ($isElearning == 1) ? 'E-learning' : 'Residenziale';
    }

    public static function isEligible($eligibility)
    {
        return ($eligibility == 1) ? 'Si' : 'No';
    }
    /**
     * Function to Format a number from a Float
     *
     * @param Float $number
     * @return Float
     */
    public static function numberFormat($number)
    {
        return number_format((float)$number, 2);
    }


}
