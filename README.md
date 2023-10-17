# PSR Forms

Very simple stateless form renderer and handler, compliant with PSR request and response interfaces.

Still a work in progress.

Currently utilises the Formr library (https://github.com/formr/formr) as a base.

## Example usage

### Simple implementation

```php
$factory = new \Click66\Forms\Factory();
$formr = \Click66\Forms\Formr\Adaptr();

$formRenderer = $factory->makeFormRenderer($formr);
$formHandler = $factory->makeFormHandler($formr);

# In your POST route:
$response = $formHandler->process(
    $request,   # PSR-7 RequestInterface
    ['Name(required)', 'Message(required)'],
    function ($data) {
        var_dump($data);    # $data contains the validated form data
        
        if (true) { # Do something with your data
            return new \Click66\Forms\Result\Success('Thank you for using the service!');   # Messages flashed to session
        } else {
            return new \Click66\Forms\Result\Failure('Something has gone wrong, please try again later.');
        }
    }
)->respond(fn () => response(303)->withHeader('Location', '/form'));    # Return a PSR response. Will run at conclusion of form processing, regardless of outcome.
```

### Captcha usage
```php
$captcha = new \Click66\Forms\Captcha\Captcha(
    new \Click66\Forms\Captcha\Credentials(...),
    new \Click66\Forms\Captcha\RecaptchaV2\Verifier(...),   # Currently supports Google ReCaptcha v2.
)
$factory = (new \Click66\Forms\Factory())->withCaptcha($captcha);

# The rest is the same - CAPTCHA will be processed and validated automatically
```
