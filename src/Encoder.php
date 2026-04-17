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
     * @return self<S, O>
     */
    public function contramap(callable $f): self;

    /**
     * @template P
     * @param Encoder<O, P> $next
     * @return self<T, P>
     */
    public function andThen(Encoder $next): self;
}
