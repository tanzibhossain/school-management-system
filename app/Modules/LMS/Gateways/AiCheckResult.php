<?php

namespace App\Modules\LMS\Gateways;

final class AiCheckResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?int $aiScore = null,
        public readonly ?bool $likelyAiGenerated = null,
        public readonly ?string $originalityNote = null,
        public readonly ?array $rawResponse = null,
        public readonly ?string $errorMessage = null,
    ) {}

    public static function success(int $aiScore, bool $likelyAiGenerated, string $originalityNote, ?array $rawResponse = null): self
    {
        return new self(true, $aiScore, $likelyAiGenerated, $originalityNote, $rawResponse);
    }

    public static function failure(string $errorMessage): self
    {
        return new self(false, errorMessage: $errorMessage);
    }
}
