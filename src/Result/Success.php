<?php

declare(strict_types=1);

namespace Click66\Forms\Result;

final class Success extends Result
{
    public function isSuccessful(): bool
    {
        return true;
    }
}