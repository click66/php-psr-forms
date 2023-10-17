<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Slim\App;
use Slim\Factory\AppFactory;
use Laminas\Diactoros\Response;
use Click66\Forms\Captcha\Captcha;
use Click66\Forms\Captcha\Credentials;
use Click66\Forms\Captcha\Verifier;
use Click66\Forms\Factory;
use Click66\Forms\Formr\Adaptr;
use Click66\Forms\Renderer;
use Click66\Forms\Result\{Failure, Success};
use Laminas\Diactoros\ServerRequest as DiactorosServerRequest;
use Laminas\Diactoros\Uri;

class ContactFormTest extends TestCase
{
    private Adaptr $adaptr;

    private Factory $factory;

    private App $app;

    private bool $processed = false;

    protected function setUp(): void
    {
        $this->adaptr = new Adaptr();
        $this->factory = new Factory();

        $this->app = AppFactory::create();

        $this->app->get('/contact', function ($request) {
            return new Response();
        });

        $this->app->post('/contact', function ($request) {
            return $this->factory->makeFormHandler($this->adaptr)->process(
                $request,
                ['name(required)', 'email(required)'],
                function () {
                    $this->processed = true;
                    return new Success('All is well');
                }
            )
                ->respond(fn () => (new Response(status: 303, headers: ['Location' => '/contact'])));
        });

        $this->app->post('/contactfailure', function ($request) {
            return $this->factory->makeFormHandler($this->adaptr)->process(
                $request,
                ['name(required)', 'email(required)'],
                function () {
                    return new Failure('Something has gone wrong');
                }
            )
                ->respond(fn () => (new Response(status: 303, headers: ['Location' => '/contact'])));
        });
    }

    public function testPostContactFormSuccess(): void
    {
        // Given a contact form
        $adaptr = new Adaptr();

        // When the form is submitted with the required data
        $request = (new DiactorosServerRequest())->withMethod('POST')->withUri(new Uri('/contact'))->withParsedBody([
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
        ]);
        $response = $this->app->handle($request);

        // Then the process logic ran
        $this->assertTrue($this->processed);

        // And the expected success message displays when the form is rendered
        $messages = (new Renderer($adaptr))->messages();
        $this->assertStringContainsString('All is well', $messages);

        // And the handler returns a redirect response
        $this->assertSame(303, $response->getStatusCode());
        $this->assertSame('/contact', $response->getHeader('Location')[0]);
    }

    public function testPostContactFormValidationFailure(): void
    {
        // Given a contact form
        $adaptr = new Adaptr();

        // When the form is submitted with the "name" field missing
        $request = (new DiactorosServerRequest())->withMethod('POST')->withUri(new Uri('/contact'))->withParsedBody([
            'name' => '',
            'email' => 'johndoe@example.com',
        ]);
        $response = $this->app->handle($request);

        // And the expected failure message displays when the form is rendered
        $messages = (new Renderer($adaptr))->messages();
        $this->assertStringContainsString('name is required', $messages);

        // And the handler returns a redirect response
        $this->assertSame(303, $response->getStatusCode());
        $this->assertSame('/contact', $response->getHeader('Location')[0]);
    }

    public function testPostContactFormCustomFailure(): void
    {
        // Given a contact form
        $adaptr = new Adaptr();

        // When the form is submitted with the required data
        $request = (new DiactorosServerRequest())->withMethod('POST')->withUri(new Uri('/contactfailure'))->withParsedBody([
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
        ]);
        $response = $this->app->handle($request);

        // Then the process logic has not ran
        $this->assertFalse($this->processed);

        // And the expected failure message displays when the form is rendered
        $messages = (new Renderer($adaptr))->messages();
        $this->assertStringContainsString('Something has gone wrong', $messages);

        // And the handler returns a redirect response
        $this->assertSame(303, $response->getStatusCode());
        $this->assertSame('/contact', $response->getHeader('Location')[0]);
    }

    public function testCaptchaMissingData(): void
    {
        // Given a contact form with a captcha
        $adaptr = new Adaptr();
        $this->factory = $this->factory->withCaptcha(new Captcha(new Credentials('foo', 'bar'), $this->createMock(Verifier::class)));

        // When the form is submitted without any captcha data
        $request = (new DiactorosServerRequest())->withMethod('POST')->withUri(new Uri('/contactfailure'))->withParsedBody([
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
        ]);
        $response = $this->app->handle($request);

        // Then the expected failure message displays when the form is rendered
        $messages = (new Renderer($adaptr))->messages();
        $this->assertStringContainsString('There has been an error accepting your request', $messages);

        // And the process logic has not ran
        $this->assertFalse($this->processed);

        // And the response is as expected
        $this->assertSame(303, $response->getStatusCode());
        $this->assertSame('/contact', $response->getHeader('Location')[0]);
    }

    public function testFailedCaptchaVerification(): void
    {
        // Given a contact form with a captcha
        $adaptr = new Adaptr();
        /** @var \PHPUnit\Framework\MockObject\MockObject&Verifier */
        $verifier = $this->createMock(Verifier::class);
        $this->factory = $this->factory->withCaptcha(new Captcha(new Credentials('foo', 'bar'), $verifier));

        // And the captcha verification will fail
        $verifier->method('verify')->willReturn(false);

        // When the form is submitted with the required data
        $request = (new DiactorosServerRequest())->withMethod('POST')->withUri(new Uri('/contactfailure'))->withParsedBody([
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'g-recaptcha-response' => 'foobar',
        ]);
        $response = $this->app->handle($request);

        // Then the expected failure message displays when the form is rendered
        $messages = (new Renderer($adaptr))->messages();

        // And the process logic has not ran
        $this->assertFalse($this->processed);

        // And the response is as expected
        $this->assertSame(303, $response->getStatusCode());
        $this->assertSame('/contact', $response->getHeader('Location')[0]);
        $this->assertStringContainsString('There has been an error accepting your request', $messages);
    }
}
