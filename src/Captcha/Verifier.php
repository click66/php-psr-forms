<?php

declare(strict_types=1);

namespace Click66\Forms\Captcha;

interface Verifier
{
    public function verify(string $captchaResponse, Credentials $credentials): bool;
}
