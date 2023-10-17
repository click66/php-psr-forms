<?php

declare(strict_types=1);

namespace Click66\Forms;

use Click66\Forms\Captcha\Captcha;
use Click66\Forms\Formr\Adaptr;

final readonly class Factory
{
    public function __construct(private ?Captcha $captcha = null)
    {
    }

    public function withCaptcha(Captcha $captcha): static
    {
        return new static($captcha);
    }

    public function makeFormHandler(Adaptr $adaptr): Handler
    {
        return new Handler(
            $adaptr,
            $this->captcha,
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
