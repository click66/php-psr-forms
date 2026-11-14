<?php

declare(strict_types=1);

namespace Tests\Integration;

use Click66\Forms\Captcha\Captcha;
use Click66\Forms\Captcha\Credentials;
use Click66\Forms\Captcha\Verifier;
use Click66\Forms\Factory;
use Click66\Forms\Formr\Adaptr;
use Click66\Forms\Renderer;
use Click66\Forms\Result\Success;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use PHPUnit\Framework\TestCase;

class CsrfProtectionTest extends TestCase
{
    private bool $processed;

    public static function setUpBeforeClass(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.use_cookies', '0');
            ini_set('session.cache_limiter', '');
            session_start();
        }
    }

    protected function setUp(): void
    {
        $_SESSION = [];
        $this->processed = false;
    }

    public function testSubmissionWithTheRenderedTokenIsProcessed(): void
    {
        // Given a CSRF-protected form has been rendered
        $factory = (new Factory())->withCsrfProtection();
        $token = $this->renderedToken($factory);

        // When it is submitted with the token from the rendered form
        $messages = $this->submit($factory, ['csrf_token' => $token]);

        // Then the submission is processed
        $this->assertTrue($this->processed);
        $this->assertStringContainsString('All is well', $messages);
    }

    public function testSubmissionWithoutATokenIsRejected(): void
    {
        // Given a CSRF-protected form has been rendered
        $factory = (new Factory())->withCsrfProtection();
        $this->renderedToken($factory);

        // When it is submitted without a token
        $messages = $this->submit($factory, []);

        // Then the submission is rejected
        $this->assertFalse($this->processed);
        $this->assertStringContainsString('Your session has expired', $messages);
    }

    public function testSubmissionWithAForgedTokenIsRejected(): void
    {
        // Given a CSRF-protected form has been rendered
        $factory = (new Factory())->withCsrfProtection();
        $this->renderedToken($factory);

        // When it is submitted with a token that was not issued to this session
        $messages = $this->submit($factory, ['csrf_token' => str_repeat('a', 64) . '|' . (time() + 3600)]);

        // Then the submission is rejected
        $this->assertFalse($this->processed);
        $this->assertStringContainsString('Your session has expired', $messages);
    }

    public function testSubmissionWithAnExpiredTokenIsRejected(): void
    {
        // Given a CSRF-protected form was rendered with a token that has since expired
        $factory = (new Factory())->withCsrfProtection();
        [$token] = explode('|', $this->renderedToken($factory));

        // When it is submitted with that token
        $messages = $this->submit($factory, ['csrf_token' => $token . '|' . (time() - 1)]);

        // Then the submission is rejected
        $this->assertFalse($this->processed);
        $this->assertStringContainsString('Your session has expired', $messages);
    }

    public function testSubmissionIsRejectedWhenNoTokenWasEverIssued(): void
    {
        // Given a CSRF-protected form that has never been rendered in this session
        $factory = (new Factory())->withCsrfProtection();

        // When it is submitted with a made-up token
        $messages = $this->submit($factory, ['csrf_token' => 'made-up|' . (time() + 3600)]);

        // Then the submission is rejected
        $this->assertFalse($this->processed);
        $this->assertStringContainsString('Your session has expired', $messages);
    }

    public function testAddingACaptchaKeepsCsrfProtection(): void
    {
        // Given a form with CSRF protection and a captcha that always passes
        $verifier = $this->createMock(Verifier::class);
        $verifier->method('verify')->willReturn(true);
        $factory = (new Factory())
            ->withCsrfProtection()
            ->withCaptcha(new Captcha(new Credentials('site', 'secret'), $verifier));
        $this->renderedToken($factory);

        // When it is submitted with a valid captcha but no CSRF token
        $messages = $this->submit($factory, ['g-recaptcha-response' => 'ok']);

        // Then the submission is still rejected
        $this->assertFalse($this->processed);
        $this->assertStringContainsString('Your session has expired', $messages);
    }

    public function testFormsWithoutCsrfProtectionDoNotRequireAToken(): void
    {
        // Given a form without CSRF protection
        $factory = new Factory();

        // When it is submitted without a token
        $messages = $this->submit($factory, []);

        // Then the submission is processed
        $this->assertTrue($this->processed);
        $this->assertStringContainsString('All is well', $messages);
    }

    private function renderedToken(Factory $factory): string
    {
        $html = $factory->makeFormRenderer(new Adaptr())->csrf(3600);
        $this->assertSame(1, preg_match('/name="csrf_token" value="([^"]+)"/', $html, $match), $html);

        return $match[1];
    }

    /** @param array<string, string> $fields */
    private function submit(Factory $factory, array $fields): string
    {
        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withUri(new Uri('/contact'))
            ->withParsedBody(['name' => 'John Doe'] + $fields);

        $factory->makeFormHandler(new Adaptr())->process(
            $request,
            ['name(required)'],
            function () {
                $this->processed = true;
                return new Success('All is well');
            },
        );

        return (new Renderer(new Adaptr()))->messages();
    }
}
