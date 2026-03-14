<?php

declare(strict_types=1);

namespace Raoh\Boundary\Array_;

use Raoh\Absent;
use Raoh\CallableDecoder;
use Raoh\Combinator\Combiner;
use Raoh\Decoder;
use Raoh\Decoders;
use Raoh\ErrorCodes;
use Raoh\FieldDecoder;
use Raoh\DecoderTrait;
use Raoh\Path;
use Raoh\Present;
use Raoh\PresentNull;
use Raoh\Result;
use Raoh\Builtin\BoolDecoder;
use Raoh\Builtin\FloatDecoder;
use Raoh\Builtin\IntDecoder;
use Raoh\Builtin\StringDecoder;

/**
 * Internal implementation for the Array_ boundary.
 * Public API is exposed via functions.php.
 */
final class ArrayDecoders
{
    private function __construct()
    {
    }

    /** @return StringDecoder<mixed> */
    public static function string_(): StringDecoder
    {
        $base = CallableDecoder::of(function (mixed $in, ?Path $path = null): Result {
            $p = $path ?? Path::root();
            if ($in === null) {
                return Result::fail($p, ErrorCodes::Required->value, 'is required');
            }
            if (!is_string($in)) {
                return Result::fail(
                    $p,
                    ErrorCodes::TypeMismatch->value,
                    'expected string',
                    ['expected' => 'string', 'actual' => gettype($in)],
                );
            }
            return Result::ok($in);
        });
        return new StringDecoder($base);
    }

    /** @return IntDecoder<mixed> */
    public static function int_(): IntDecoder
    {
        return new IntDecoder(CallableDecoder::of(function (mixed $in, ?Path $path = null): Result {
            $p = $path ?? Path::root();
            if ($in === null) {
                return Result::fail($p, ErrorCodes::Required->value, 'is required');
            }
            if (is_int($in)) {
                return Result::ok($in);
            }
            if (is_string($in) && is_numeric($in) && !str_contains($in, '.')) {
                return Result::ok((int) $in);
            }
            return Result::fail(
                $p,
                ErrorCodes::TypeMismatch->value,
                'expected integer',
                ['expected' => 'integer', 'actual' => gettype($in)],
            );
        }));
    }

    /** @return FloatDecoder<mixed> */
    public static function float_(): FloatDecoder
    {
        return new FloatDecoder(CallableDecoder::of(function (mixed $in, ?Path $path = null): Result {
            $p = $path ?? Path::root();
            if ($in === null) {
                return Result::fail($p, ErrorCodes::Required->value, 'is required');
            }
            if (is_float($in) || is_int($in)) {
                return Result::ok((float) $in);
            }
            if (is_string($in) && is_numeric($in)) {
                return Result::ok((float) $in);
            }
            return Result::fail(
                $p,
                ErrorCodes::TypeMismatch->value,
                'expected number',
                ['expected' => 'float', 'actual' => gettype($in)],
            );
        }));
    }

    /** @return BoolDecoder<mixed> */
    public static function bool_(): BoolDecoder
    {
        return new BoolDecoder(CallableDecoder::of(function (mixed $in, ?Path $path = null): Result {
            $p = $path ?? Path::root();
            if ($in === null) {
                return Result::fail($p, ErrorCodes::Required->value, 'is required');
            }
            if (is_bool($in)) {
                return Result::ok($in);
            }
            return Result::fail(
                $p,
                ErrorCodes::TypeMismatch->value,
                'expected boolean',
                ['expected' => 'boolean', 'actual' => gettype($in)],
            );
        }));
    }

    /**
     * Required field from an associative array.
     *
     * @template T
     * @param Decoder<mixed, T> $dec
     * @return FieldDecoder&Decoder<array, T>
     */
    public static function field(string $name, Decoder $dec): FieldDecoder
    {
        return new class ($name, $dec) implements FieldDecoder {
            use DecoderTrait;

            public function __construct(
                private readonly string $name,
                private readonly Decoder $dec,
            ) {
            }

            public function fieldName(): string
            {
                return $this->name;
            }

            public function decode(mixed $in, ?Path $path = null): Result
            {
                $p = $path ?? Path::root();
                $fieldPath = $p->append($this->name);
                if (!is_array($in) || !array_key_exists($this->name, $in)) {
                    return Result::fail($fieldPath, ErrorCodes::Required->value, 'is required');
                }
                return $this->dec->decode($in[$this->name], $fieldPath);
            }
        };
    }

    /**
     * Optional field — returns null when the key is absent.
     *
     * @template T
     * @param Decoder<mixed, T> $dec
     * @return Decoder<array<string, mixed>, T|null>
     */
    public static function optionalField(string $name, Decoder $dec): Decoder
    {
        return CallableDecoder::of(function (mixed $in, ?Path $path = null) use ($name, $dec): Result {
            if (!is_array($in) || !array_key_exists($name, $in)) {
                return Result::ok(null);
            }
            return $dec->decode($in[$name], ($path ?? Path::root())->append($name));
        });
    }

    /**
     * Three-state field: Absent | PresentNull | Present<T>
     * Useful for PATCH endpoints.
     *
     * @template T
     * @param Decoder<mixed, T> $dec
     * @return Decoder<array<string, mixed>, Absent|PresentNull|Present<T>>
     */
    public static function optionalNullableField(string $name, Decoder $dec): Decoder
    {
        return CallableDecoder::of(function (mixed $in, ?Path $path = null) use ($name, $dec): Result {
            if (!is_array($in) || !array_key_exists($name, $in)) {
                return Result::ok(new Absent());
            }
            if ($in[$name] === null) {
                return Result::ok(new PresentNull());
            }
            return $dec->decode($in[$name], ($path ?? Path::root())->append($name))
                ->map(fn ($v) => new Present($v));
        });
    }

    /**
     * Validates that the input is an associative array (object), then delegates to $dec.
     * Use when a field's value is itself a nested object.
     *
     * @template T
     * @param Decoder<array<string, mixed>, T> $dec
     * @return Decoder<mixed, T>
     */
    public static function nested(Decoder $dec): Decoder
    {
        return CallableDecoder::of(function (mixed $in, ?Path $path = null) use ($dec): Result {
            $p = $path ?? Path::root();
            if ($in === null) {
                return Result::fail($p, ErrorCodes::Required->value, 'is required');
            }
            if (!is_array($in) || array_is_list($in)) {
                return Result::fail(
                    $p,
                    ErrorCodes::TypeMismatch->value,
                    'expected object',
                    ['expected' => 'object', 'actual' => gettype($in)],
                );
            }
            return $dec->decode($in, $p);
        });
    }

    /**
     * Decode each element of a list, accumulating all errors.
     *
     * @template T
     * @param Decoder<mixed, T> $elementDec
     * @return Decoder<mixed, list<T>>
     */
    public static function listOf(Decoder $elementDec): Decoder
    {
        return CallableDecoder::of(function (mixed $in, ?Path $path = null) use ($elementDec): Result {
            $p = $path ?? Path::root();
            if ($in === null) {
                return Result::fail($p, ErrorCodes::Required->value, 'is required');
            }
            if (!is_array($in) || !array_is_list($in)) {
                return Result::fail(
                    $p,
                    ErrorCodes::TypeMismatch->value,
                    'expected array',
                    ['expected' => 'array', 'actual' => gettype($in)],
                );
            }
            return Result::traverse(
                $in,
                fn (mixed $item, Path $itemPath) => $elementDec->decode($item, $itemPath),
                $p,
            );
        });
    }

    /**
     * Allow null values; delegates to $dec for non-null.
     *
     * @template T
     * @param Decoder<mixed, T> $dec
     * @return Decoder<mixed, T|null>
     */
    public static function nullable(Decoder $dec): Decoder
    {
        return CallableDecoder::of(function (mixed $in, ?Path $path = null) use ($dec): Result {
            if ($in === null) {
                return Result::ok(null);
            }
            return $dec->decode($in, $path);
        });
    }

    /** @param Decoder<mixed, mixed> ...$decoders */
    public static function combine(Decoder ...$decoders): Combiner
    {
        return Decoders::combine(...$decoders);
    }

    /**
     * Decode a PHP BackedEnum from its backing value.
     *
     * @template T of \BackedEnum
     * @param class-string<T> $enumClass
     * @return Decoder<mixed, T>
     */
    public static function enumOf(string $enumClass): Decoder
    {
        if (!is_subclass_of($enumClass, \BackedEnum::class)) {
            throw new \InvalidArgumentException("{$enumClass} is not a BackedEnum");
        }
        return CallableDecoder::of(function (mixed $in, ?Path $path = null) use ($enumClass): Result {
            $p = $path ?? Path::root();
            if ($in === null) {
                return Result::fail($p, ErrorCodes::Required->value, 'is required');
            }
            try {
                $case = $enumClass::from($in);
                return Result::ok($case);
            } catch (\ValueError) {
                return Result::fail(
                    $p,
                    ErrorCodes::InvalidValue->value,
                    'invalid value',
                    ['actual' => $in],
                );
            }
        });
    }

    /**
     * Match an exact literal value.
     *
     * @return Decoder<mixed, mixed>
     */
    public static function literal(mixed $expected): Decoder
    {
        return CallableDecoder::of(function (mixed $in, ?Path $path = null) use ($expected): Result {
            $p = $path ?? Path::root();
            if ($in !== $expected) {
                return Result::fail(
                    $p,
                    ErrorCodes::InvalidValue->value,
                    "expected " . (json_encode($expected) ?: 'null'),
                    ['expected' => $expected, 'actual' => $in],
                );
            }
            return Result::ok($in);
        });
    }
}
