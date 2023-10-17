<?php

declare(strict_types=1);

namespace Tests\Unit\Forms;

use PHPUnit\Framework\TestCase;
use Click66\Forms\Captcha\Captcha;
use Click66\Forms\Captcha\Credentials;
use Click66\Forms\Captcha\Verifier;
use Click66\Forms\Factory;
use Click66\Forms\Formr\Adaptr;
use Click66\Forms\Renderer;

class RendererTest extends TestCase
{
    public function testOpenRendersOpenTag(): void
    {
        // Given a form with action of "my-action"
        $adaptr = new Adaptr();

        // When I render the form open tag
        $sut = new Renderer($adaptr);
        $result = $sut->open('/my-action');

        // Then the expected HTML will be returned
        $this->assertStringContainsString('<form ', $result);
        $this->assertStringContainsString('action="/my-action"', $result);
        $this->assertStringContainsString('method="post"', $result);
    }

    public function testCloseRendersCloseTag(): void
    {
        // Given a form with action of "my-action"
        $adaptr = new Adaptr();

        // When I render the form close tag
        $sut = new Renderer($adaptr);
        $result = $sut->close();

        $this->assertStringContainsString('</form>', $result);
    }

    public function testRendersInputButton(): void
    {
        // Given a form with action of "my-action"
        $adaptr = new Adaptr();

        // When I render the button
        $sut = new Renderer($adaptr);
        $result = $sut->button();

        $this->assertStringContainsString('<button type="submit" ', $result);
    }

    public function testRendersCaptcha(): void
    {
        // Given a form with action of "my-action" and some captcha credentials set
        $adaptr = new Adaptr();
        $factory = (new Factory())
            ->withCaptcha(new Captcha(new Credentials('my$iteId', 'my$ecret'), $this->createMock(Verifier::class)));

        // When I render the captcha
        $sut = $factory->makeFormRenderer($adaptr);
        $result = $sut->captcha();

        // Then the expected HTML is returned
        $this->assertEquals(
            '<div class="g-recaptcha recaptcha" data-sitekey="my$iteId"></div>',
            $result,
        );
    }

    public function testRendersBlankIfNoCaptchaCredentials(): void
    {
        // Given a form with action of "my-action" and no Captcha credentials
        $adaptr = new Adaptr();
        $factory = new Factory();

        // When I try and render a captcha
        $sut = $factory->makeFormRenderer($adaptr);
        $result = $sut->captcha();

        // Then a blank string is returned
        $this->assertSame('', $result);
    }

    public function testRendersTextInputDefault(): void
    {
        // Given a form
        $adaptr = new Adaptr();

        // When I try and render an input field with a name and placeholder
        $sut = new Renderer($adaptr);
        $result = $sut->text('name');

        // Then text field is returned
        $this->assertStringContainsString('input type="text"', $result);
        $this->assertStringContainsString('name="name"', $result);
        $this->assertStringContainsString('placeholder=""', $result);
        $this->assertStringNotContainsString('required', $result);
    }

    public function testRendersTextInputWithPlaceholder(): void
    {
        // Given a form
        $adaptr = new Adaptr();

        // When I try and render an input field with a name and placeholder
        $sut = new Renderer($adaptr);
        $result = $sut->text('name', placeholder: 'Enter your name');

        // Then text field is returned
        $this->assertStringContainsString('name="name"', $result);
        $this->assertStringContainsString('placeholder="Enter your name"', $result);
        $this->assertStringNotContainsString('required', $result);
    }

    public function testRendersTextInputRequired(): void
    {
        // Given a form
        $adaptr = new Adaptr();

        // When I try and render an input field with a name and placeholder
        $sut = new Renderer($adaptr);
        $result = $sut->text('name', required: true);

        // Then text field is returned
        $this->assertStringContainsString('name="name"', $result);
        $this->assertStringContainsString('required', $result);
    }

    public function testRendersTextareaInputDefault(): void
    {
        // Given a form
        $adaptr = new Adaptr();

        // When I try and render an input field with a name and placeholder
        $sut = new Renderer($adaptr);
        $result = $sut->textarea('name');

        // Then text field is returned
        $this->assertStringContainsString('textarea', $result);
        $this->assertStringContainsString('placeholder=""', $result);
        $this->assertStringNotContainsString('required', $result);
    }

    public function testRendersTextareaInputWithPlaceholder(): void
    {
        // Given a form
        $adaptr = new Adaptr();

        // When I try and render an input field with a name and placeholder
        $sut = new Renderer($adaptr);
        $result = $sut->textarea('name', placeholder: 'Enter your name');

        // Then text field is returned
        $this->assertStringContainsString('name="name"', $result);
        $this->assertStringContainsString('placeholder="Enter your name"', $result);
        $this->assertStringNotContainsString('required', $result);
    }

    public function testRendersTextareaInputRequired(): void
    {
        // Given a form
        $adaptr = new Adaptr();

        // When I try and render an input field with a name and placeholder
        $sut = new Renderer($adaptr);
        $result = $sut->textarea('name', required: true);

        // Then text field is returned
        $this->assertStringContainsString('name="name"', $result);
        $this->assertStringContainsString('required', $result);
    }
}
