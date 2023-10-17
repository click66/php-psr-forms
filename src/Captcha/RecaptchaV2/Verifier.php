<?php

declare(strict_types=1);

namespace Click66\Forms\Captcha\RecaptchaV2;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Click66\Forms\Captcha\Credentials;
use Click66\Forms\Captcha\Verifier as VerifierInterface;

final readonly class Verifier implements VerifierInterface
{
    public function __construct(
        private ClientInterface $client,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
    ) {
    }

    public function verify(string $captchaResponse, Credentials $credentials): bool
    {
        $result = $this->client->sendRequest(
            $this->requestFactory->createRequest('POST', 'https://www.google.com/recaptcha/api/siteverify')
                ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
                ->withBody($this->streamFactory->createStream(http_build_query([
                    'secret' => $credentials->secretKey,
                    'response' => $captchaResponse,
                ]))),
        );

        $resultBody = json_decode($result->getBody()->getContents(), true);

        return isset($resultBody['success']) && $resultBody['success'] === true;
    }
}
