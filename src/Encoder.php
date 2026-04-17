<?php

declare(strict_types=1);

namespace Raoh;

/**
 * @template T
 * @template O
 */
interface Encoder
{
    /**
     * @param T $value
     * @return O
     */
    public function encode(mixed $value): mixed;

    /**
     * @template S
     * @param callable(S): T $f
     * @return Encoder<S, O>
     */
    public function contramap(callable $f): Encoder;

    /**
     * @template P
     * @param Encoder<O, P> $next
     * @return Encoder<T, P>
     */
    public function andThen(Encoder $next): Encoder;
}
