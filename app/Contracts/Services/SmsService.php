<?php

namespace App\Contracts\Services;

interface SmsService
{
    public function send(string $phone, string $message): void;
}
