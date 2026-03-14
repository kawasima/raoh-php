<?php

declare(strict_types=1);

namespace Raoh;

/**
 * Tri-state field presence — useful for PATCH APIs where:
 * - Absent: field not present in the request (don't update)
 * - PresentNull: field explicitly set to null (set to null)
 * - Present<T>: field present with a value (update to value)
 */
abstract readonly class Presence
{
}
