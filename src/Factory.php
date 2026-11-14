<?php

declare(strict_types=1);

namespace Click66\Forms;

use Click66\Forms\Captcha\Captcha;
use Click66\Forms\Formr\Adaptr;

final readonly class Factory
{
    public function __construct(
        private ?Captcha $captcha = null,
        private bool $csrfProtection = false,
    ) {
    }

    public function withCaptcha(Captcha $captcha): static
    {
        return new static($captcha, $this->csrfProtection);
    }

    public function withCsrfProtection(): static
    {
        return new static($this->captcha, true);
    }

    public function makeFormHandler(Adaptr $adaptr): Handler
    {
        return new Handler(
            $adaptr,
            $this->captcha,
            $this->csrfProtection,
        );
    }

    public function makeFormRenderer(Adaptr $adaptr): Renderer
    {
        return new Renderer(
            $adaptr,
            $this->captcha,
        );
    }
}
