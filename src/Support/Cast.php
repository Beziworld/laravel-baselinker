<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Support;

use Carbon\CarbonImmutable;

/**
 * Lenient conversions of loosely typed API values.
 *
 * BaseLinker returns numbers as strings in some places, booleans as "1"/"0"
 * and dates as unix timestamps where 0 means "not set".
 *
 * @internal
 */
final class Cast
{
    public static function int(mixed $input): ?int
    {
        if (is_int($input)) {
            return $input;
        }

        if (is_bool($input)) {
            return (int) $input;
        }

        return is_numeric($input) ? (int) $input : null;
    }

    public static function float(mixed $input): ?float
    {
        return is_numeric($input) ? (float) $input : null;
    }

    public static function string(mixed $input): ?string
    {
        if (is_string($input)) {
            return $input;
        }

        return is_int($input) || is_float($input) ? (string) $input : null;
    }

    /**
     * Interprets true/false, 1/0, "1"/"0", "true"/"false" and "yes"/"no"; anything else is null.
     */
    public static function bool(mixed $input): ?bool
    {
        if (is_bool($input)) {
            return $input;
        }

        if (is_int($input) || is_float($input)) {
            return $input !== 0 && $input !== 0.0;
        }

        if (is_string($input)) {
            return match (strtolower(trim($input))) {
                '1', 'true', 'yes', 'y' => true,
                '0', 'false', 'no', 'n', '' => false,
                default => null,
            };
        }

        return null;
    }

    /**
     * Unix timestamp to a UTC date; 0, empty and malformed values are null.
     */
    public static function date(mixed $input): ?CarbonImmutable
    {
        $timestamp = self::int($input);

        return $timestamp !== null && $timestamp > 0 ? CarbonImmutable::createFromTimestampUTC($timestamp) : null;
    }

    /**
     * @return array<array-key, mixed>
     */
    public static function arr(mixed $input): array
    {
        return is_array($input) ? $input : [];
    }

    /**
     * List of array records; scalar entries are skipped and keys are dropped.
     *
     * @return list<array<string, mixed>>
     */
    public static function records(mixed $input): array
    {
        $records = [];

        foreach (self::arr($input) as $record) {
            if (is_array($record)) {
                /** @var array<string, mixed> $record */
                $records[] = $record;
            }
        }

        return $records;
    }

    /**
     * Map of numeric values, for example prices per price group or stock per warehouse.
     *
     * @return array<string, float>
     */
    public static function floats(mixed $input): array
    {
        $values = [];

        foreach (self::arr($input) as $key => $item) {
            $number = self::float($item);

            if ($number !== null) {
                $values[(string) $key] = $number;
            }
        }

        return $values;
    }

    /**
     * List of strings, scalars converted.
     *
     * @return list<string>
     */
    public static function strings(mixed $input): array
    {
        $values = [];

        foreach (self::arr($input) as $item) {
            $text = self::string($item);

            if ($text !== null) {
                $values[] = $text;
            }
        }

        return $values;
    }

    /**
     * List of integers, non-numeric entries skipped.
     *
     * @return list<int>
     */
    public static function intList(mixed $input): array
    {
        $values = [];

        foreach (self::arr($input) as $item) {
            $number = self::int($item);

            if ($number !== null) {
                $values[] = $number;
            }
        }

        return $values;
    }

    /**
     * Non-empty string or null.
     */
    public static function text(mixed $input): ?string
    {
        $text = self::string($input);

        return $text !== null && $text !== '' ? $text : null;
    }
}
