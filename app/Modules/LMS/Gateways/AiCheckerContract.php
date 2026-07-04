<?php

namespace App\Modules\LMS\Gateways;

/**
 * Any other AI provider implements this and gets bound in AppServiceProvider
 * in place of AnthropicAiChecker — nothing else in the module changes. Unlike
 * Sms's SmsGatewayContract (a stub, since no live SMS credentials existed to
 * test against), this one is wired to a real provider per the confirmed
 * decision — Claude is a real, available provider and per-school API keys
 * are the whole point of the DevPlan's "Head Teacher enters AI API key"
 * design.
 */
interface AiCheckerContract
{
    public function check(string $apiKey, string $content): AiCheckResult;
}
