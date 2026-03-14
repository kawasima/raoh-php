<?php

declare(strict_types=1);

namespace Raoh;

use Raoh\Combinator\Combiner;
use Raoh\Err;
use Raoh\Ok;

final class Decoders
{
    private function __construct()
    {
    }

    /**
     * Combine multiple decoders with applicative error accumulation.
     *
     * @param Decoder<mixed, mixed> ...$decoders
     */
    public static function combine(Decoder ...$decoders): Combiner
    {
        return new Combiner(array_values($decoders));
    }

    /**
     * Lazily-evaluated decoder — useful for recursive/self-referential structures.
     *
     * @template I
     * @template T
     * @param callable(): Decoder<I, T> $supplier
     * @return Decoder<I, T>
     */
    public static function lazy(callable $supplier): Decoder
    {
        return CallableDecoder::of(
            fn (mixed $in, ?Path $path = null) => $supplier()->decode($in, $path),
        );
    }

    /**
     * Use $fallback only when the decoder fails with all "required" issues
     * (i.e., the field is absent). Non-required errors are preserved.
     *
     * @template I
     * @template T
     * @param Decoder<I, T> $dec
     * @param T|callable(Issues): T $fallback
     * @return Decoder<I, T>
     */
    public static function withDefault(Decoder $dec, mixed $fallback): Decoder
    {
        return CallableDecoder::of(function (mixed $in, ?Path $path = null) use ($dec, $fallback): Result {
            $r = $dec->decode($in, $path);
            if ($r instanceof Ok) {
                return $r;
            }
            assert($r instanceof Err);
            if (self::allRequired($r->issues)) {
                $value = is_callable($fallback) ? $fallback($r->issues) : $fallback;
                return Result::ok($value);
            }
            return $r;
        });
    }

    /**
     * Use $fallback for any error (more permissive than withDefault).
     *
     * @template I
     * @template T
     * @param Decoder<I, T> $dec
     * @param T|callable(Issues): T $fallback
     * @return Decoder<I, T>
     */
    public static function recover(Decoder $dec, mixed $fallback): Decoder
    {
        return CallableDecoder::of(function (mixed $in, ?Path $path = null) use ($dec, $fallback): Result {
            $r = $dec->decode($in, $path);
            if ($r instanceof Ok) {
                return $r;
            }
            assert($r instanceof Err);
            $value = is_callable($fallback) ? $fallback($r->issues) : $fallback;
            return Result::ok($value);
        });
    }

    /**
     * Try each candidate decoder in order; return first success or accumulated errors.
     *
     * @template I
     * @template T
     * @param Decoder<I, T> ...$candidates
     * @return Decoder<I, T>
     */
    public static function oneOf(Decoder ...$candidates): Decoder
    {
        return CallableDecoder::of(function (mixed $in, ?Path $path = null) use ($candidates): Result {
            $allIssues = Issues::empty();
            foreach ($candidates as $dec) {
                $r = $dec->decode($in, $path);
                if ($r instanceof Ok) {
                    return $r;
                }
                assert($r instanceof Err);
                $allIssues = $allIssues->merge($r->issues);
            }
            return Result::err($allIssues);
        });
    }

    /**
     * Wrap a decoder to reject unknown keys in the input array.
     *
     * @template I of array
     * @template T
     * @param Decoder<I, T> $dec
     * @param list<string> $knownFields
     * @return Decoder<I, T>
     */
    public static function strict(Decoder $dec, array $knownFields): Decoder
    {
        $knownSet = array_flip($knownFields);
        return CallableDecoder::of(function (mixed $in, ?Path $path = null) use ($dec, $knownSet): Result {
            $resolvedPath = $path ?? Path::root();
            $issues = Issues::empty();
            if (is_array($in) && !array_is_list($in)) {
                foreach (array_keys($in) as $key) {
                    if (!isset($knownSet[$key])) {
                        $issues = $issues->add(Issue::of(
                            $resolvedPath->append((string) $key),
                            ErrorCodes::UnknownField->value,
                            'unknown field',
                            ['field' => $key],
                        ));
                    }
                }
            }
            $result = $dec->decode($in, $resolvedPath);
            if ($issues->isEmpty()) {
                return $result;
            }
            if ($result instanceof Ok) {
                return Result::err($issues);
            }
            assert($result instanceof Err);
            return Result::err($result->issues->merge($issues));
        });
    }

    private static function allRequired(Issues $issues): bool
    {
        if ($issues->isEmpty()) {
            return false;
        }
        foreach ($issues->toArray() as $issue) {
            if ($issue->code !== ErrorCodes::Required->value) {
                return false;
            }
        }
        return true;
    }
}
