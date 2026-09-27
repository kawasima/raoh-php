<?php

declare(strict_types=1);

namespace Raoh;

/**
 * Refines an {@see ErrorCodes} value into the constraint that actually failed.
 *
 * `code` classifies a failure for a program to branch on; `messageKey` says which
 * wording describes it. Several constraints share one code — `positive()`, `min()`
 * and `range()` all report `out_of_range` — but no single sentence fits all three,
 * and their metadata does not carry the same placeholders. Every key here is the
 * code it refines, a dot, and a qualifier, matching kawasima/raoh's `MessageKeys`.
 */
enum MessageKeys: string
{
    case OutOfRangeMinimum     = 'out_of_range.minimum';
    case OutOfRangeMaximum     = 'out_of_range.maximum';
    case OutOfRangeRange       = 'out_of_range.range';
    case OutOfRangePositive    = 'out_of_range.positive';
    case OutOfRangeNegative    = 'out_of_range.negative';
    case OutOfRangeNonNegative = 'out_of_range.non_negative';
    case OutOfRangeNonPositive = 'out_of_range.non_positive';

    case InvalidFormatEmail      = 'invalid_format.email';
    case InvalidFormatUrl        = 'invalid_format.url';
    case InvalidFormatUuid       = 'invalid_format.uuid';
    case InvalidFormatUlid       = 'invalid_format.ulid';
    case InvalidFormatIp         = 'invalid_format.ip';
    case InvalidFormatIpv4       = 'invalid_format.ipv4';
    case InvalidFormatIpv6       = 'invalid_format.ipv6';
    case InvalidFormatStartsWith = 'invalid_format.starts_with';
    case InvalidFormatEndsWith   = 'invalid_format.ends_with';
    case InvalidFormatIncludes   = 'invalid_format.includes';
}
