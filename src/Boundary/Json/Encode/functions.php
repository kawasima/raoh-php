<?php

declare(strict_types=1);

namespace Raoh\Boundary\Json\Encode;

use Raoh\CallableEncoder;
use Raoh\Encoder;

/**
 * Import with:
 *   use function Raoh\Boundary\Json\Encode\to_json;
 */

/**
 * Wraps an array encoder to produce a JSON string.
 *
 * @param Encoder<mixed, array<mixed>> $enc
 * @return Encoder<mixed, string>
 */
function to_json(Encoder $enc): Encoder
{
    return CallableEncoder::of(
        fn(mixed $value): string => json_encode($enc->encode($value), JSON_THROW_ON_ERROR)
    );
}
