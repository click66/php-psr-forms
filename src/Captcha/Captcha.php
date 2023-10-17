<?php

declare(strict_types=1);

namespace Click66\Forms\Captcha;

final readonly class Captcha
{
    public function __construct(
        private readonly Credentials $credentials,
        private readonly Verifier $verifier
    ) {
    }

    public function getSiteKey(): string
    {
        return $this->credentials->siteKey;
    }

    public function verify(string $response): bool
    {
        return $this->verifier->verify($response, $this->credentials);
    }
}
