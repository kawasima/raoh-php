<?php

declare(strict_types=1);

namespace Raoh;

/**
 * Adapts a plain closure to the Encoder interface.
 *
 * @template T
 * @template O
 * @implements Encoder<T, O>
 */
final class CallableEncoder implements Encoder
{
    private function __construct(private readonly \Closure $fn)
    {
    }

    /**
     * @template TT
     * @template OO
     * @param callable(TT): OO $fn
     * @return self<TT, OO>
     */
    public static function of(callable $fn): self
    {
        return new self(\Closure::fromCallable($fn));
    }

    public function encode(mixed $value): mixed
    {
        return ($this->fn)($value);
    }

    /**
     * @template S
     * @param callable(S): T $f
     * @return self<S, O>
     */
    public function contramap(callable $f): self
    {
        return self::of(fn(mixed $v): mixed => $this->encode($f($v)));
    }

    /**
     * @template P
     * @param Encoder<O, P> $next
     * @return self<T, P>
     */
    public function andThen(Encoder $next): self
    {
        return self::of(fn(mixed $v): mixed => $next->encode($this->encode($v)));
    }
}
