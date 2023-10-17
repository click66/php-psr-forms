<?php

declare(strict_types=1);

namespace Click66\Forms\Result;

final class Failure extends Result
{
    public function isSuccessful(): bool
    {
        return false;
    }
}
