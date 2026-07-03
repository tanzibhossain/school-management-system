<?php

namespace App\Modules\Sms\Gateways;

use App\Modules\School\Models\School;

/**
 * Any real provider (SSL Wireless, Twilio, etc.) implements this and gets
 * bound in AppServiceProvider in place of LogGateway — nothing else in the
 * module changes. $school carries the per-school sms_api_key/sms_sender_id
 * credentials (unlike Payment's gateways, which take a dedicated
 * PaymentConfig model — SMS has no separate config table, since the two
 * columns already live on School itself).
 */
interface SmsGatewayContract
{
    public function send(School $school, string $phone, string $body): SmsGatewayResult;
}
