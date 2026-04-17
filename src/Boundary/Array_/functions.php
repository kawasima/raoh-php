<?php

declare(strict_types=1);

namespace Raoh\Boundary\Array_;

use Raoh\Builtin\BoolDecoder;
use Raoh\Builtin\FloatDecoder;
use Raoh\Builtin\IntDecoder;
use Raoh\Builtin\StringDecoder;
use Raoh\Combinator\Combiner;
use Raoh\Decoder;
use Raoh\FieldDecoder;

/**
 * Import these functions with:
 *   use function Raoh\Boundary\Array_\{field, string_, int_, float_, bool_, combine, nested, list_of, nullable};
 */

/** @return StringDecoder<mixed> */
function string_(): StringDecoder
{
    return ArrayDecoders::string_();
}

/** @return IntDecoder<mixed> */
function int_(): IntDecoder
{
    return ArrayDecoders::int_();
}

/** @return FloatDecoder<mixed> */
function float_(): FloatDecoder
{
    return ArrayDecoders::float_();
}

/** @return BoolDecoder<mixed> */
function bool_(): BoolDecoder
{
    return ArrayDecoders::bool_();
}

/**
 * @template T
 * @param Decoder<mixed, T> $dec
 * @return FieldDecoder&Decoder<array<string, mixed>, T>
 */
function field(string $name, Decoder $dec): FieldDecoder
{
    return ArrayDecoders::field($name, $dec);
}

/**
 * @template T
 * @param Decoder<mixed, T> $dec
 * @return Decoder<array<string, mixed>, T|null>
 */
function optional_field(string $name, Decoder $dec): Decoder
{
    return ArrayDecoders::optionalField($name, $dec);
}

/**
 * @template T
 * @param Decoder<mixed, T> $dec
 * @return Decoder<array<string, mixed>, \Raoh\Absent|\Raoh\PresentNull|\Raoh\Present<T>>
 */
function optional_nullable_field(string $name, Decoder $dec): Decoder
{
    return ArrayDecoders::optionalNullableField($name, $dec);
}

/**
 * @template T
 * @param Decoder<array<string, mixed>, T> $dec
 * @return Decoder<mixed, T>
 */
function nested(Decoder $dec): Decoder
{
    return ArrayDecoders::nested($dec);
}

/**
 * @template T
 * @param Decoder<mixed, T> $dec
 * @return Decoder<mixed, list<T>>
 */
function list_of(Decoder $dec): Decoder
{
    return ArrayDecoders::listOf($dec);
}

/**
 * @template T
 * @param Decoder<mixed, T> $dec
 * @return Decoder<mixed, T|null>
 */
function nullable(Decoder $dec): Decoder
{
    return ArrayDecoders::nullable($dec);
}

/** @param Decoder<mixed, mixed> ...$decoders */
function combine(Decoder ...$decoders): Combiner
{
    return ArrayDecoders::combine(...$decoders);
}

/**
 * @template T of \UnitEnum
 * @param class-string<T> $enumClass
 * @return Decoder<mixed, T>
 */
function enum_of(string $enumClass): Decoder
{
    return ArrayDecoders::enumOf($enumClass);
}

/** @return Decoder<mixed, mixed> */
function literal(mixed $expected): Decoder
{
    return ArrayDecoders::literal($expected);
}

/** @return Decoder<mixed, string> */
function bytes(): Decoder
{
    return ArrayDecoders::bytes();
}
