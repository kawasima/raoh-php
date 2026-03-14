<?php

declare(strict_types=1);

namespace Raoh;

/**
 * Provides a static factory method `of()` as a first-class callable shorthand
 * for constructor invocation in decoder chains.
 *
 * Usage:
 *   class User {
 *       use \Raoh\StaticConstructor;
 *       public function __construct(
 *           public readonly string $email,
 *           public readonly int    $age,
 *       ) {}
 *   }
 *
 *   combine(
 *       field('email', string_()->email()),
 *       field('age',   int_()->range(0, 150)),
 *   )->map(User::of(...));
 *
 * PHP does not support `new ClassName(...)` as a first-class callable,
 * so this trait bridges that gap.
 */
trait StaticConstructor
{
    public static function of(mixed ...$args): static
    {
        return new static(...$args);
    }
}
