<?php

namespace App\Modules\Sms\Gateways;

final class SmsGatewayResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $errorMessage = null,
    ) {}

    public static function success(): self
    {
        return new self(true);
    }

    public static function failure(string $errorMessage): self
    {
        return new self(false, $errorMessage);
    }
}
