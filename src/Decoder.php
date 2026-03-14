<?php

declare(strict_types=1);

namespace Raoh;

/**
 * @template I
 * @template T
 */
interface Decoder
{
    /**
     * Decode input, optionally at a given path.
     * When $path is null, Path::root() is used.
     *
     * @param I $in
     * @return Result<T>
     */
    public function decode(mixed $in, ?Path $path = null): Result;
}
