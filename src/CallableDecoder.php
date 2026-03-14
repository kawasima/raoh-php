<?php

declare(strict_types=1);

namespace Raoh;

/**
 * Adapts a plain closure to the Decoder interface.
 *
 * @template I
 * @template T
 * @implements Decoder<I, T>
 */
final class CallableDecoder implements Decoder
{
    /** @use DecoderTrait<I, T> */
    use DecoderTrait;

    private function __construct(private readonly \Closure $fn)
    {
    }

    public static function of(callable $fn): self
    {
        return new self(\Closure::fromCallable($fn));
    }

    public function decode(mixed $in, ?Path $path = null): Result
    {
        return ($this->fn)($in, $path ?? Path::root());
    }
}
