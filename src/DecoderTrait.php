<?php

declare(strict_types=1);

namespace Raoh;

/**
 * Provides default combinators for any class implementing Decoder.
 *
 * @template I
 * @template T
 */
trait DecoderTrait
{
    /**
     * @template U
     * @param callable(T): U $f
     * @return Decoder<I, U>
     */
    public function map(callable $f): Decoder
    {
        return CallableDecoder::of(
            fn (mixed $in, ?Path $path = null) => $this->decode($in, $path)->map($f),
        );
    }

    /**
     * @template U
     * @param callable(T): Result<U> $f
     * @return Decoder<I, U>
     */
    public function flatMap(callable $f): Decoder
    {
        return CallableDecoder::of(function (mixed $in, ?Path $path = null) use ($f): Result {
            $resolvedPath = $path ?? Path::root();
            return $this->decode($in, $resolvedPath)->flatMap(
                function (mixed $value) use ($f, $resolvedPath): Result {
                    $r = $f($value);
                    if ($r instanceof Err) {
                        return Result::err($r->issues->rebase($resolvedPath));
                    }
                    return $r;
                },
            );
        });
    }

    /**
     * @template U
     * @param Decoder<T, U> $next
     * @return Decoder<I, U>
     */
    public function pipe(Decoder $next): Decoder
    {
        return CallableDecoder::of(
            fn (mixed $in, ?Path $path = null) => $this->decode($in, $path)
                ->flatMap(fn (mixed $v) => $next->decode($v, $path)),
        );
    }

    /**
     * @return Decoder<list<I>, list<T>>
     */
    public function asList(): Decoder
    {
        return CallableDecoder::of(
            fn (mixed $items, ?Path $path = null) => Result::traverse(
                $items,
                fn (mixed $item, Path $p) => $this->decode($item, $p),
                $path ?? Path::root(),
            ),
        );
    }
}
