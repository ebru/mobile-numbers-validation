<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BaseController extends Controller
{
    /**
     * Validates the number if it is formatted correctly for South America
     *
     * @param string $number
     * @return boolean
     */
    public function validateNumber(string $number): bool
    {
        if (preg_match('/^(\+?27)[6-8][0-9]{8}$/', $number) === 1) {
            return true;
        }

        return false;
    }

    /**
     * Attempts to correct number format to validate
     *
     * @param string $number
     * @return array
     */
    public function correctNumber(string $number): array
    {
        // Check if country code is missing
        if (strlen($number) === 9) {
            $addedCountryCodeNumber = '27'.$number;

            if ($this->validateNumber($addedCountryCodeNumber)) {
                return [
                    'is_corrected' => true,
                    'modified_number' => $addedCountryCodeNumber
                ];
            }
        }

        // Check if parsed updated number is valid after eliminating the deleted part
        $parsedNumber = explode("_DELETE", $number)[0];

        if ($this->validateNumber($parsedNumber)) {
            return [
                'is_corrected' => true,
                'modified_number' => $parsedNumber
            ];
        }

        // Check if updated number is valid with country code added
        $addedCountryCodeParsedNumber = '27'.$parsedNumber;

        if ($this->validateNumber($addedCountryCodeParsedNumber)) {
            return [
                'is_corrected' => true,
                'modified_number' => $addedCountryCodeParsedNumber
            ];
        }

        return [
            'is_corrected' => false,
            'modified_number' => null
        ];
    }
}
