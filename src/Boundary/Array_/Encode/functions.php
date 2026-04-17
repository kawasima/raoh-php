<?php

declare(strict_types=1);

namespace Raoh\Boundary\Array_\Encode;

use Raoh\CallableEncoder;
use Raoh\Encoder;

/**
 * Import these functions with:
 *   use function Raoh\Boundary\Array_\Encode\{string_, int_, float_, bool_, date_, date_time_,
 *       enum_of, nullable, with_default, property, object_, nested, list_};
 */

/** @return Encoder<string, string> */
function string_(): Encoder
{
    return CallableEncoder::of(fn(string $v): string => $v);
}

/** @return Encoder<int, int> */
function int_(): Encoder
{
    return CallableEncoder::of(fn(int $v): int => $v);
}

/** @return Encoder<float, float> */
function float_(): Encoder
{
    return CallableEncoder::of(fn(float $v): float => $v);
}

/** @return Encoder<bool, bool> */
function bool_(): Encoder
{
    return CallableEncoder::of(fn(bool $v): bool => $v);
}

/** @return Encoder<\DateTimeInterface, string> */
function date_(): Encoder
{
    return CallableEncoder::of(fn(\DateTimeInterface $v): string => $v->format('Y-m-d'));
}

/** @return Encoder<\DateTimeInterface, string> */
function date_time_(): Encoder
{
    return CallableEncoder::of(fn(\DateTimeInterface $v): string => $v->format(\DateTimeInterface::ATOM));
}

/**
 * Encodes a PHP enum to its backing value (BackedEnum) or case name (pure enum).
 *
 * Note: unlike the Decoder counterpart enum_of(string $enumClass), no class
 * argument is required here — encoding cannot fail, so per-class type safety
 * is unnecessary. The asymmetry in arity is intentional.
 *
 * @return Encoder<\UnitEnum, string|int>
 */
function enum_of(): Encoder
{
    return CallableEncoder::of(function (\UnitEnum $v): string|int {
        if ($v instanceof \BackedEnum) {
            return $v->value;
        }
        return $v->name;
    });
}

/**
 * @template T
 * @param Encoder<T, mixed> $enc
 * @return Encoder<T|null, mixed>
 */
function nullable(Encoder $enc): Encoder
{
    return CallableEncoder::of(fn(mixed $v): mixed => $v === null ? null : $enc->encode($v));
}

/**
 * @template T
 * @param Encoder<T, mixed> $enc
 * @param T $default
 * @return Encoder<T|null, mixed>
 */
function with_default(Encoder $enc, mixed $default): Encoder
{
    return CallableEncoder::of(fn(mixed $v): mixed => $enc->encode($v ?? $default));
}

/**
 * @template T
 * @param callable(T): mixed $getter
 * @param Encoder<mixed, mixed> $enc
 * @return PropertyEncoder<T>
 */
function property(string $key, callable $getter, Encoder $enc): PropertyEncoder
{
    return new PropertyEncoder($key, \Closure::fromCallable($getter), $enc);
}

/**
 * @template T
 * @param PropertyEncoder<T> ...$props
 * @return Encoder<T, array<string, mixed>>
 */
function object_(PropertyEncoder ...$props): Encoder
{
    return CallableEncoder::of(function (mixed $value) use ($props): array {
        $result = [];
        foreach ($props as $prop) {
            $result[$prop->key] = $prop->encode($value);
        }
        return $result;
    });
}

/**
 * Signals that an object encoder is used as a nested value inside a parent object.
 *
 * This is an identity function — no transformation is performed. It exists purely
 * to mirror the decoder-side nested() at the call site and signal to readers that
 * the encoder produces a structured sub-object, not a scalar value.
 *
 * @param Encoder<mixed, array<string, mixed>> $enc
 * @return Encoder<mixed, array<string, mixed>>
 */
function nested(Encoder $enc): Encoder
{
    return $enc;
}

/**
 * @template T
 * @param Encoder<T, mixed> $enc
 * @return Encoder<list<T>, list<mixed>>
 */
function list_(Encoder $enc): Encoder
{
    /** @var CallableEncoder<list<T>, list<mixed>> */
    $result = CallableEncoder::of(function (array $items) use ($enc): array {
        return array_map(fn(mixed $item): mixed => $enc->encode($item), $items);
    });
    return $result;
}
