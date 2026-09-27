<?php

declare(strict_types=1);

namespace Raoh\Builtin;

use Raoh\CallableDecoder;
use Raoh\Decoder;
use Raoh\DecoderTrait;
use Raoh\ErrorCodes;
use Raoh\MessageKeys;
use Raoh\Path;
use Raoh\Result;

/**
 * Fluent string constraint chain using the Decorator pattern.
 * Each constraint method wraps the previous decoder and returns a new StringDecoder.
 *
 * @template I
 * @implements Decoder<I, string>
 */
final class StringDecoder implements Decoder
{
    /** @use DecoderTrait<I, string> */
    use DecoderTrait;

    /** @param Decoder<I, string> $inner */
    public function __construct(private readonly Decoder $inner)
    {
    }

    public function decode(mixed $in, ?Path $path = null): Result
    {
        return $this->inner->decode($in, $path ?? Path::root());
    }

    // -------------------------------------------------------------------------
    // Transforms
    // -------------------------------------------------------------------------

    public function trim(): static
    {
        return new static($this->chain(
            fn (string $v, Path $_p) => Result::ok(trim($v)),
        ));
    }

    public function toLowerCase(): static
    {
        return new static($this->chain(
            fn (string $v, Path $_p) => Result::ok(mb_strtolower($v)),
        ));
    }

    public function toUpperCase(): static
    {
        return new static($this->chain(
            fn (string $v, Path $_p) => Result::ok(mb_strtoupper($v)),
        ));
    }

    // -------------------------------------------------------------------------
    // Presence constraints
    // -------------------------------------------------------------------------

    public function nonBlank(?string $message = null): static
    {
        return new static($this->chain(function (string $v, Path $p) use ($message): Result {
            if (trim($v) === '') {
                return Result::failWith(
                    $p,
                    ErrorCodes::Blank->value,
                    ErrorCodes::Blank->value,
                    $message,
                    'must not be blank',
                );
            }
            return Result::ok($v);
        }));
    }

    public function allowBlank(): static
    {
        return $this; // No-op: blanks are allowed by default
    }

    // -------------------------------------------------------------------------
    // Length constraints
    // -------------------------------------------------------------------------

    public function minLength(int $n, ?string $message = null): static
    {
        return new static($this->chain(function (string $v, Path $p) use ($n, $message): Result {
            if (mb_strlen($v) < $n) {
                return Result::failWith(
                    $p,
                    ErrorCodes::TooShort->value,
                    ErrorCodes::TooShort->value,
                    $message,
                    "must be at least {$n} characters",
                    ['min' => $n, 'actual' => mb_strlen($v)],
                );
            }
            return Result::ok($v);
        }));
    }

    public function maxLength(int $n, ?string $message = null): static
    {
        return new static($this->chain(function (string $v, Path $p) use ($n, $message): Result {
            if (mb_strlen($v) > $n) {
                return Result::failWith(
                    $p,
                    ErrorCodes::TooLong->value,
                    ErrorCodes::TooLong->value,
                    $message,
                    "must be at most {$n} characters",
                    ['max' => $n, 'actual' => mb_strlen($v)],
                );
            }
            return Result::ok($v);
        }));
    }

    public function fixedLength(int $n, ?string $message = null): static
    {
        return new static($this->chain(function (string $v, Path $p) use ($n, $message): Result {
            if (mb_strlen($v) !== $n) {
                return Result::failWith(
                    $p,
                    ErrorCodes::InvalidLength->value,
                    ErrorCodes::InvalidLength->value,
                    $message,
                    "must be exactly {$n} characters",
                    ['length' => $n, 'actual' => mb_strlen($v)],
                );
            }
            return Result::ok($v);
        }));
    }

    // -------------------------------------------------------------------------
    // Pattern / format constraints
    // -------------------------------------------------------------------------

    public function pattern(string $regex, ?string $code = null, ?string $message = null): static
    {
        $code ??= ErrorCodes::InvalidFormat->value;
        if (@preg_match($regex, '') === false) {
            throw new \InvalidArgumentException("Invalid regular expression: {$regex}");
        }
        return new static($this->chain(function (string $v, Path $p) use ($regex, $code, $message): Result {
            if (!preg_match($regex, $v)) {
                return Result::failWith(
                    $p,
                    $code,
                    $code,
                    $message,
                    'invalid format',
                    ['pattern' => $regex],
                );
            }
            return Result::ok($v);
        }));
    }

    public function startsWith(string $prefix, ?string $message = null): static
    {
        return new static($this->chain(function (string $v, Path $p) use ($prefix, $message): Result {
            if (!str_starts_with($v, $prefix)) {
                return Result::failWith(
                    $p,
                    ErrorCodes::InvalidFormat->value,
                    MessageKeys::InvalidFormatStartsWith->value,
                    $message,
                    "must start with '{$prefix}'",
                    ['prefix' => $prefix],
                );
            }
            return Result::ok($v);
        }));
    }

    public function endsWith(string $suffix, ?string $message = null): static
    {
        return new static($this->chain(function (string $v, Path $p) use ($suffix, $message): Result {
            if (!str_ends_with($v, $suffix)) {
                return Result::failWith(
                    $p,
                    ErrorCodes::InvalidFormat->value,
                    MessageKeys::InvalidFormatEndsWith->value,
                    $message,
                    "must end with '{$suffix}'",
                    ['suffix' => $suffix],
                );
            }
            return Result::ok($v);
        }));
    }

    public function includes(string $substring, ?string $message = null): static
    {
        return new static($this->chain(function (string $v, Path $p) use ($substring, $message): Result {
            if (!str_contains($v, $substring)) {
                return Result::failWith(
                    $p,
                    ErrorCodes::InvalidFormat->value,
                    MessageKeys::InvalidFormatIncludes->value,
                    $message,
                    "must include '{$substring}'",
                    ['substring' => $substring],
                );
            }
            return Result::ok($v);
        }));
    }

    /**
     * @param list<string> $allowed
     */
    public function oneOf(array $allowed, ?string $message = null): static
    {
        return new static($this->chain(function (string $v, Path $p) use ($allowed, $message): Result {
            if (!in_array($v, $allowed, true)) {
                return Result::failWith(
                    $p,
                    ErrorCodes::InvalidValue->value,
                    ErrorCodes::InvalidValue->value,
                    $message,
                    'invalid value',
                    ['allowed' => $allowed, 'actual' => $v],
                );
            }
            return Result::ok($v);
        }));
    }

    // -------------------------------------------------------------------------
    // Format-specific constraints
    // -------------------------------------------------------------------------

    public function email(?string $message = null): static
    {
        return new static($this->chain(function (string $v, Path $p) use ($message): Result {
            if (!filter_var($v, FILTER_VALIDATE_EMAIL) || mb_strlen($v) > 254) {
                return Result::failWith(
                    $p,
                    ErrorCodes::InvalidFormat->value,
                    MessageKeys::InvalidFormatEmail->value,
                    $message,
                    'not a valid email address',
                );
            }
            return Result::ok($v);
        }));
    }

    public function url(?string $message = null): static
    {
        return new static($this->chain(function (string $v, Path $p) use ($message): Result {
            if (!filter_var($v, FILTER_VALIDATE_URL)) {
                return Result::failWith(
                    $p,
                    ErrorCodes::InvalidFormat->value,
                    MessageKeys::InvalidFormatUrl->value,
                    $message,
                    'not a valid URL',
                );
            }
            $parsed = parse_url($v);
            if (!is_array($parsed)) {
                return Result::failWith(
                    $p,
                    ErrorCodes::InvalidFormat->value,
                    MessageKeys::InvalidFormatUrl->value,
                    $message,
                    'not a valid URL',
                );
            }
            $scheme = $parsed['scheme'] ?? null;
            if (!in_array($scheme, ['http', 'https'], true)) {
                return Result::failWith(
                    $p,
                    ErrorCodes::InvalidFormat->value,
                    MessageKeys::InvalidFormatUrl->value,
                    $message,
                    'not a valid URL',
                );
            }
            $port = isset($parsed['port']) ? (int) $parsed['port'] : null;
            if ($port !== null && ($port < 1 || $port > 65535)) {
                return Result::failWith(
                    $p,
                    ErrorCodes::InvalidFormat->value,
                    MessageKeys::InvalidFormatUrl->value,
                    $message,
                    'not a valid URL',
                );
            }
            return Result::ok($v);
        }));
    }

    public function uuid(?string $message = null): static
    {
        return new static($this->chain(function (string $v, Path $p) use ($message): Result {
            if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $v)) {
                return Result::failWith(
                    $p,
                    ErrorCodes::InvalidFormat->value,
                    MessageKeys::InvalidFormatUuid->value,
                    $message,
                    'not a valid UUID',
                );
            }
            return Result::ok($v);
        }));
    }

    public function ulid(?string $message = null): static
    {
        return new static($this->chain(function (string $v, Path $p) use ($message): Result {
            if (!preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/', $v)) {
                return Result::failWith(
                    $p,
                    ErrorCodes::InvalidFormat->value,
                    MessageKeys::InvalidFormatUlid->value,
                    $message,
                    'not a valid ULID',
                );
            }
            return Result::ok($v);
        }));
    }

    public function ip(?string $message = null): static
    {
        return new static($this->chain(function (string $v, Path $p) use ($message): Result {
            if (!filter_var($v, FILTER_VALIDATE_IP)) {
                return Result::failWith(
                    $p,
                    ErrorCodes::InvalidFormat->value,
                    MessageKeys::InvalidFormatIp->value,
                    $message,
                    'not a valid IP address',
                );
            }
            return Result::ok($v);
        }));
    }

    public function ipv4(?string $message = null): static
    {
        return new static($this->chain(function (string $v, Path $p) use ($message): Result {
            if (!filter_var($v, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return Result::failWith(
                    $p,
                    ErrorCodes::InvalidFormat->value,
                    MessageKeys::InvalidFormatIpv4->value,
                    $message,
                    'not a valid IPv4 address',
                );
            }
            return Result::ok($v);
        }));
    }

    public function ipv6(?string $message = null): static
    {
        return new static($this->chain(function (string $v, Path $p) use ($message): Result {
            if (!filter_var($v, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                return Result::failWith(
                    $p,
                    ErrorCodes::InvalidFormat->value,
                    MessageKeys::InvalidFormatIpv6->value,
                    $message,
                    'not a valid IPv6 address',
                );
            }
            return Result::ok($v);
        }));
    }

    // -------------------------------------------------------------------------
    // Type conversions (return different decoder types)
    // -------------------------------------------------------------------------

    /** @return IntDecoder<I> */
    public function toInt(?string $message = null): IntDecoder
    {
        return new IntDecoder(CallableDecoder::of(
            function (mixed $in, ?Path $path = null) use ($message): Result {
                return $this->decode($in, $path)->flatMap(
                    function (string $v) use ($path, $message): Result {
                        $p = $path ?? Path::root();
                        if (!is_numeric($v) || str_contains($v, '.')) {
                            return Result::failWith(
                                $p,
                                ErrorCodes::TypeMismatch->value,
                                ErrorCodes::TypeMismatch->value,
                                $message,
                                'expected integer',
                                ['expected' => 'integer'],
                            );
                        }
                        return Result::ok((int) $v);
                    },
                );
            },
        ));
    }

    /** @return FloatDecoder<I> */
    public function toFloat(?string $message = null): FloatDecoder
    {
        return new FloatDecoder(CallableDecoder::of(
            function (mixed $in, ?Path $path = null) use ($message): Result {
                return $this->decode($in, $path)->flatMap(
                    function (string $v) use ($path, $message): Result {
                        $p = $path ?? Path::root();
                        if (!is_numeric($v)) {
                            return Result::failWith(
                                $p,
                                ErrorCodes::TypeMismatch->value,
                                ErrorCodes::TypeMismatch->value,
                                $message,
                                'expected number',
                                ['expected' => 'float'],
                            );
                        }
                        return Result::ok((float) $v);
                    },
                );
            },
        ));
    }

    /** @return BoolDecoder<I> */
    public function toBool(?string $message = null): BoolDecoder
    {
        return new BoolDecoder(CallableDecoder::of(
            function (mixed $in, ?Path $path = null) use ($message): Result {
                return $this->decode($in, $path)->flatMap(
                    function (string $v) use ($path, $message): Result {
                        $p = $path ?? Path::root();
                        $parsed = match (strtolower($v)) {
                            'true', '1', 'yes', 'on'  => true,
                            'false', '0', 'no', 'off' => false,
                            default                    => null,
                        };
                        if ($parsed === null) {
                            return Result::failWith(
                                $p,
                                ErrorCodes::TypeMismatch->value,
                                ErrorCodes::TypeMismatch->value,
                                $message,
                                'expected boolean',
                                ['expected' => 'boolean'],
                            );
                        }
                        return Result::ok($parsed);
                    },
                );
            },
        ));
    }

    /** @return Decoder<I, \DateTimeImmutable> */
    public function toDate(string $format = 'Y-m-d', ?string $message = null): Decoder
    {
        return CallableDecoder::of(
            function (mixed $in, ?Path $path = null) use ($format, $message): Result {
                return $this->decode($in, $path)->flatMap(
                    function (string $v) use ($format, $path, $message): Result {
                        $p = $path ?? Path::root();
                        $date = \DateTimeImmutable::createFromFormat($format, $v);
                        if ($date === false || $date->format($format) !== $v) {
                            // PHP-specific date parsing, not Raoh's ISO-8601 date() — kept on
                            // the plain invalid_format key, not invalid_format.date.
                            return Result::failWith(
                                $p,
                                ErrorCodes::InvalidFormat->value,
                                ErrorCodes::InvalidFormat->value,
                                $message,
                                "expected date in format {$format}",
                                ['format' => $format],
                            );
                        }
                        return Result::ok($date);
                    },
                );
            },
        );
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * @return Decoder<I, string>
     */
    private function chain(callable $constraint): Decoder
    {
        return CallableDecoder::of(
            fn (mixed $in, ?Path $path = null): Result => $this->decode($in, $path)
                ->flatMap(fn (string $v): Result => $constraint($v, $path ?? Path::root())),
        );
    }
}
