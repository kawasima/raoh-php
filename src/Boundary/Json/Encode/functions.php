<?php

declare(strict_types=1);

namespace Raoh\Boundary\Json\Encode;

use Raoh\Encoder;

/**
 * Import with:
 *   use function Raoh\Boundary\Json\Encode\to_json;
 */

/**
 * Wraps an array encoder to produce a JSON string.
 *
 * @param Encoder<mixed, array<mixed>> $enc
 * @return \Closure(mixed): string
 */
function to_json(Encoder $enc): \Closure
{
    return fn(mixed $value): string => (string) json_encode($enc->encode($value));
}
