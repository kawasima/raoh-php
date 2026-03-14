<?php

declare(strict_types=1);

namespace Raoh;

/**
 * @template T
 */
final readonly class Present extends Presence
{
    /**
     * @param T $value
     */
    public function __construct(public readonly mixed $value)
    {
    }
}
