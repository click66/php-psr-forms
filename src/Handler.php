<?php

declare(strict_types=1);

namespace Click66\Forms;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Click66\Forms\Captcha\Captcha;
use Click66\Forms\Exception\MissingCaptchaCredentials;
use Click66\Forms\Formr\Adaptr;
use Click66\Forms\Result\Result;

final readonly class Handler
{
    public function __construct(
        private Adaptr $adaptr,
        private ?Captcha $captcha = null,
    ) {
    }

    public function process(ServerRequestInterface $request, array $fields, callable $function): static
    {
        $this->adaptr->setRequest($request);

        $data = $this->adaptr->validate(implode(',', $fields));

        if ($this->adaptr->ok()) {
            if ($this->captcha && !$this->validateCaptcha($request)) {
                $this->adaptr->flashError('There has been an error accepting your request. Please try again.');
                return $this;
            }

            $result = $function($data);

            if ($result instanceof Result) {
                if ($result->isSuccessful()) {
                    $this->adaptr->flashSuccess($result->message);
                } else {
                    $this->adaptr->flashError($result->message);
                }
            }
        }

        return $this;
    }

    public function respond(callable $function): ResponseInterface
    {
        return $function();
    }

    private function validateCaptcha(ServerRequestInterface $request): bool
    {
        if ($this->captcha === null) {
            throw new MissingCaptchaCredentials('No Captcha was passed to the form handler.');
        }

        $parsedBody = $request->getParsedBody();

        if (isset($parsedBody['g-recaptcha-response']) && !empty($parsedBody['g-recaptcha-response'])) {
            return $this->captcha->verify($parsedBody['g-recaptcha-response']);
        }

        return false;
    }
}
