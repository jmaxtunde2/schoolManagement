<?php

namespace App\Services\Sms;

class SmsResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $externalId = null,
        public readonly ?string $error = null,
    ) {}

    public static function success(?string $externalId = null): self
    {
        return new self(true, $externalId);
    }

    public static function failure(string $error): self
    {
        return new self(false, null, $error);
    }
}
