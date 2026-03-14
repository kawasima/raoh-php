<?php

declare(strict_types=1);

namespace Raoh;

/**
 * A Decoder that is bound to a specific field name.
 * Used by Combiner::strict() to detect unknown fields.
 *
 * @template I
 * @template T
 * @extends Decoder<I, T>
 */
interface FieldDecoder extends Decoder
{
    public function fieldName(): string;
}
