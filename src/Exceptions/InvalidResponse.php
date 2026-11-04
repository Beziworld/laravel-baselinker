<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Exceptions;

/**
 * Raised when a successful response lacks data the method promises, for example a label file.
 */
final class InvalidResponse extends BaseLinkerException
{
    public static function missingFile(string $field): self
    {
        return new self(sprintf('BaseLinker returned no file in the "%s" field.', $field));
    }

    public static function malformedFile(string $field): self
    {
        return new self(sprintf('BaseLinker returned a file in the "%s" field that is not valid base64.', $field));
    }
}
