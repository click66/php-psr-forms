<?php

declare(strict_types=1);

namespace Click66\Forms\Result;

abstract class Result
{
    public function __construct(
        public string $message,
    ) {
    }

    abstract public function isSuccessful(): bool;
}
