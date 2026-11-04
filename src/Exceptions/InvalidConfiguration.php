<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Exceptions;

/**
 * Raised when the package configuration is incomplete or refers to an unknown connection.
 */
final class InvalidConfiguration extends BaseLinkerException
{
    public static function missingToken(string $connection): self
    {
        return new self(sprintf('BaseLinker connection "%s" has no API token. Set BASELINKER_TOKEN or the token of the connection in config/baselinker.php.', $connection));
    }

    public static function unknownConnection(string $name): self
    {
        return new self(sprintf('BaseLinker connection "%s" is not defined in config/baselinker.php.', $name));
    }

    public static function invalidValue(string $key, string $expected): self
    {
        return new self(sprintf('BaseLinker option "%s" is invalid: expected %s.', $key, $expected));
    }
}
