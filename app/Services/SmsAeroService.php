<?php

namespace App\Services;

use App\Contracts\Services\SmsService as Contract;
use SmsAero\SmsAeroMessage;

class SmsAeroService implements Contract
{
    public function send(string $phone, string $message): void
    {
        $smsAeroMessage = new SmsAeroMessage(
            config('zrenie-clinic.sms_aero.user_login'),
            config('zrenie-clinic.sms_aero.api_key'),
        );

        $smsAeroMessage->send(['number' => $phone, 'text' => $message, 'sign' => 'SMSAero']);
    }
}
