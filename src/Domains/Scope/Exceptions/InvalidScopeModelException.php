<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Domains\Scope\Exceptions;

use InvalidArgumentException;

class InvalidScopeModelException extends InvalidArgumentException
{
    public static function notAModel(string $class): self
    {
        return new self(sprintf(
            'Feature flag scope [%s] in pennantplus.scopes must be an Eloquent model.',
            $class,
        ));
    }
}
