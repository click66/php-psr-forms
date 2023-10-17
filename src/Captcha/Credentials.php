<?php

declare(strict_types=1);

namespace Click66\Forms\Captcha;

final readonly class Credentials
{
    public function __construct(
        public string $siteKey,
        public string $secretKey,
    ) {
    }
}
