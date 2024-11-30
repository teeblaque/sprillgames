<?php

namespace App\Traits;

trait HasPhoneFieldTrait
{
    /**
     *
     * @param string|int|null $phoneNumber
     * @return string
     */
    public function getPhoneNumberWithoutDialingCode(string|int $phoneNumber = null, $country_code = null): string
    {
        if (preg_match("/^\+?{$country_code}[0-9]+$/", $phoneNumber))
            $phoneNumber =  preg_replace("/^\+?{$country_code}/", 0, $phoneNumber);

        return $phoneNumber;
    }
    /**
     *
     * @param string|int|null $phoneNumber
     * @return string
     */
    public function getPhoneNumberWithDialingCode(string|int $phoneNumber = null, $country_code = null): string
    {
        return $country_code .
            (int) $this->getPhoneNumberWithoutDialingCode($phoneNumber, $country_code);
    }
}
