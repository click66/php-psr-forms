<?php

declare(strict_types=1);

namespace Click66\Forms;

use Click66\Forms\Captcha\Captcha;
use Click66\Forms\Formr\Adaptr;

final readonly class Renderer
{
    public function __construct(
        private Adaptr $adaptr,
        private ?Captcha $captcha = null,
    ) {
    }

    public function open(string $action): string
    {
        return $this->adaptr->open(action: $action);
    }

    public function close(): string
    {
        return $this->adaptr->close();
    }

    public function button(): string
    {
        return $this->adaptr->input_button_submit();
    }

    public function captcha(): string
    {
        if (!$this->captcha) {
            return '';
        }

        return "<div class=\"g-recaptcha recaptcha\" data-sitekey=\"{$this->captcha->getSiteKey()}\"></div>";
    }

    public function text(string $name, ?string $placeholder = null, bool $required = false): string
    {
        return $this->adaptr->text($name, string: $this->htmlProps(
            placeholder: $placeholder,
        ) . ($required ? ' required' : ''));
    }

    public function textarea(string $name, ?string $placeholder = null, bool $required = false): string
    {
        return $this->adaptr->textarea($name, string: $this->htmlProps(
            placeholder: $placeholder,
        ) . ($required ? ' required' : ''));
    }

    public function csrf(int $timeout): string
    {
        return $this->adaptr->csrf($timeout);
    }

    public function messages(): string
    {
        return $this->adaptr->messages('', '');
    }

    private function htmlProps(...$props): string
    {
        return implode(' ', array_map(fn ($key, $val) => "$key=\"$val\"", array_keys($props), $props));
    }
}
