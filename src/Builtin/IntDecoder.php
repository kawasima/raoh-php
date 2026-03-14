<?php

declare(strict_types=1);

namespace Raoh\Builtin;

use Raoh\Decoder;
use Raoh\DecoderTrait;
use Raoh\ErrorCodes;
use Raoh\Path;
use Raoh\Result;

/**
 * @template I
 * @implements Decoder<I, int>
 */
final class IntDecoder implements Decoder
{
    /** @use DecoderTrait<I, int> */
    use DecoderTrait;

    /** @param Decoder<I, int> $inner */
    public function __construct(private readonly Decoder $inner)
    {
    }

    public function decode(mixed $in, ?Path $path = null): Result
    {
        return $this->inner->decode($in, $path ?? Path::root());
    }

    public function min(int $n, ?string $message = null): static
    {
        return new static($this->chain(function (int $v, Path $p) use ($n, $message): Result {
            if ($v < $n) {
                return Result::fail(
                    $p,
                    ErrorCodes::TooSmall->value,
                    $message ?? "must be at least {$n}",
                    ['min' => $n, 'actual' => $v],
                );
            }
            return Result::ok($v);
        }));
    }

    public function max(int $n, ?string $message = null): static
    {
        return new static($this->chain(function (int $v, Path $p) use ($n, $message): Result {
            if ($v > $n) {
                return Result::fail(
                    $p,
                    ErrorCodes::TooBig->value,
                    $message ?? "must be at most {$n}",
                    ['max' => $n, 'actual' => $v],
                );
            }
            return Result::ok($v);
        }));
    }

    public function range(int $min, int $max, ?string $message = null): static
    {
        if ($min > $max) {
            throw new \InvalidArgumentException("range: min ({$min}) must not be greater than max ({$max})");
        }
        return new static($this->chain(function (int $v, Path $p) use ($min, $max, $message): Result {
            if ($v < $min || $v > $max) {
                return Result::fail(
                    $p,
                    ErrorCodes::OutOfRange->value,
                    $message ?? "must be between {$min} and {$max}",
                    ['min' => $min, 'max' => $max, 'actual' => $v],
                );
            }
            return Result::ok($v);
        }));
    }

    public function positive(?string $message = null): static
    {
        return new static($this->chain(function (int $v, Path $p) use ($message): Result {
            if ($v <= 0) {
                return Result::fail($p, ErrorCodes::TooSmall->value, $message ?? 'must be positive', ['actual' => $v]);
            }
            return Result::ok($v);
        }));
    }

    public function negative(?string $message = null): static
    {
        return new static($this->chain(function (int $v, Path $p) use ($message): Result {
            if ($v >= 0) {
                return Result::fail($p, ErrorCodes::TooBig->value, $message ?? 'must be negative', ['actual' => $v]);
            }
            return Result::ok($v);
        }));
    }

    public function nonNegative(?string $message = null): static
    {
        return new static($this->chain(function (int $v, Path $p) use ($message): Result {
            if ($v < 0) {
                return Result::fail($p, ErrorCodes::TooSmall->value, $message ?? 'must be non-negative', ['actual' => $v]);
            }
            return Result::ok($v);
        }));
    }

    public function nonPositive(?string $message = null): static
    {
        return new static($this->chain(function (int $v, Path $p) use ($message): Result {
            if ($v > 0) {
                return Result::fail($p, ErrorCodes::TooBig->value, $message ?? 'must be non-positive', ['actual' => $v]);
            }
            return Result::ok($v);
        }));
    }

    public function multipleOf(int $divisor, ?string $message = null): static
    {
        return new static($this->chain(function (int $v, Path $p) use ($divisor, $message): Result {
            if ($v % $divisor !== 0) {
                return Result::fail(
                    $p,
                    ErrorCodes::NotMultipleOf->value,
                    $message ?? "must be a multiple of {$divisor}",
                    ['divisor' => $divisor, 'actual' => $v],
                );
            }
            return Result::ok($v);
        }));
    }

    /**
     * @param list<int> $allowed
     */
    public function oneOf(array $allowed, ?string $message = null): static
    {
        return new static($this->chain(function (int $v, Path $p) use ($allowed, $message): Result {
            if (!in_array($v, $allowed, true)) {
                return Result::fail(
                    $p,
                    ErrorCodes::InvalidValue->value,
                    $message ?? 'invalid value',
                    ['allowed' => $allowed, 'actual' => $v],
                );
            }
            return Result::ok($v);
        }));
    }

    /**
     * @return Decoder<I, int>
     */
    private function chain(callable $constraint): Decoder
    {
        return \Raoh\CallableDecoder::of(
            fn (mixed $in, ?Path $path = null): Result => $this->decode($in, $path)
                ->flatMap(fn (int $v): Result => $constraint($v, $path ?? Path::root())),
        );
    }
}
