<?php

namespace App\Services;

use App\Contracts\Services\PhoneService as Contract;
use Propaganistas\LaravelPhone\PhoneNumber;

class PhoneService implements Contract
{
    public static function make(string $phone): string
    {
        return new PhoneNumber($phone, 'RU');
    }

    public static function makeFormatted(string $phone): string
    {
        $phoneNumber = new PhoneNumber($phone, 'RU');
        return $phoneNumber->formatNational();
    }
}
