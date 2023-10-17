<?php

declare(strict_types=1);

namespace Click66\Forms\Formr;

use Formr\Formr;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

final class Adaptr extends Formr
{
    private ServerRequestInterface $request;

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    private function catchOutputBuffer(callable $callback)
    {
        ob_start();
        $return = $callback();
        $obContents = ob_get_contents();
        ob_end_clean();

        if (!empty($obContents)) {
            return $obContents;
        }

        return $return;
    }

    // Since Formr relies on globals, hydrate the globals from a known request before the parent call
    private function callAdapted(callable $callback)
    {
        if (isset($this->request)) {
            $originalPOST = $_POST;
            $originalSERVER = $_SERVER;

            $serverParams = $this->request->getServerParams();
            foreach ($serverParams as $key => $value) {
                $_SERVER[$key] = $value;
            }
            $_SERVER['REQUEST_METHOD'] = $this->request->getMethod();

            $parsedBody = $this->request->getParsedBody();
            if (is_array($parsedBody)) {
                $_POST = $parsedBody;
            }

            $uploadedFiles = $this->request->getUploadedFiles();
            foreach ($uploadedFiles as $name => $uploadedFile) {
                if ($uploadedFile instanceof UploadedFileInterface) {
                    $_FILES[$name] = [
                        'name'     => $uploadedFile->getClientFilename(),
                        'type'     => $uploadedFile->getClientMediaType(),
                        'tmp_name' => $uploadedFile->getStream()->getMetadata('uri'),
                        'error'    => $uploadedFile->getError(),
                        'size'     => $uploadedFile->getSize(),
                    ];
                }
            }

            $result = $this->catchOutputBuffer($callback);

            $_POST = $originalPOST;
            $_SERVER = $originalSERVER;

            return $result;
        }

        $_SERVER['REQUEST_METHOD'] = 'GET';
        return $result = $this->catchOutputBuffer($callback);
    }

    public function flashError(string $message): void
    {
        $this->error_message($message, flash: true);
    }

    public function flashSuccess(string $message): void
    {
        $this->success_message($message, flash: true);
    }

    /** Process methods */

    protected function _post($name, $label = '', $rules = []): string
    {
        $result = $this->callAdapted(fn () => parent::_post($name, $label, $rules));

        $this->flashError(implode('<br />', $this->errors));

        return $result;
    }

    /** Renderer methods */

    public function submit($form_id = null): bool
    {
        return $this->callAdapted(fn () => parent::submit($form_id));
    }

    public function open($name = '', $id = '', $action = '', $method = '', $string = '', $hidden = ''): string
    {
        return $this->callAdapted(fn () => parent::open($name, $id, $action, $method, $string, $hidden));
    }

    public function close(): string
    {
        return $this->callAdapted(fn () => parent::close());
    }

    public function input_button_submit($data = '', $label = '', $value = '', $id = '', $string = ''): string
    {
        return $this->callAdapted(fn () => parent::input_button_submit($data, $label, $value, $id, $string));
    }

    public function text($name, $label = '', $value = '', $id = '', $string = '', $inline = ''): string
    {
        return $this->callAdapted(fn () => parent::text($name, $label, $value, $id, $string, $inline));
    }

    public function textarea($name, $label = '', $value = '', $id = '', $string = '', $inline = ''): string
    {
        return $this->callAdapted(fn () => parent::textarea($name, $label, $value, $id, $string, $inline));
    }

    public function csrf($timeout = 3600): string
    {
        return $this->callAdapted(fn () => parent::csrf($timeout));
    }

    public function messages($open_tag = '', $close_tag = ''): string
    {
        return $this->callAdapted(fn () => parent::messages($open_tag, $close_tag) ?? '');
    }
}
