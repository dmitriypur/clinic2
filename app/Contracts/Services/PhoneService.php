<?php

namespace App\Contracts\Services;

interface PhoneService
{
    public static function make(string $phone): string;

    public static function makeFormatted(string $phone): string;
}
