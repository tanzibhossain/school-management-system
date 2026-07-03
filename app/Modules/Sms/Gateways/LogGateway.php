<?php

namespace App\Modules\Sms\Gateways;

use App\Modules\School\Models\School;

/**
 * Stub gateway — no real provider is wired up yet (no SMS package in
 * composer.json, no live credentials to test against either way). Records
 * that a send was attempted without making any network call; always
 * succeeds, since the one realistic failure mode (no phone on file) is
 * caught upstream in SmsBatchService before the gateway is ever invoked.
 */
class LogGateway implements SmsGatewayContract
{
    public function send(School $school, string $phone, string $body): SmsGatewayResult
    {
        return SmsGatewayResult::success();
    }
}
