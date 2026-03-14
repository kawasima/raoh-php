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
 * @implements Decoder<I, float>
 */
final class FloatDecoder implements Decoder
{
    /** @use DecoderTrait<I, float> */
    use DecoderTrait;

    /** @param Decoder<I, float> $inner */
    public function __construct(private readonly Decoder $inner)
    {
    }

    public function decode(mixed $in, ?Path $path = null): Result
    {
        return $this->inner->decode($in, $path ?? Path::root());
    }

    public function min(float $n, ?string $message = null): static
    {
        return new static($this->chain(function (float $v, Path $p) use ($n, $message): Result {
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

    public function max(float $n, ?string $message = null): static
    {
        return new static($this->chain(function (float $v, Path $p) use ($n, $message): Result {
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

    public function range(float $min, float $max, ?string $message = null): static
    {
        if ($min > $max) {
            throw new \InvalidArgumentException("range: min ({$min}) must not be greater than max ({$max})");
        }
        return new static($this->chain(function (float $v, Path $p) use ($min, $max, $message): Result {
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
        return new static($this->chain(function (float $v, Path $p) use ($message): Result {
            if ($v <= 0.0) {
                return Result::fail($p, ErrorCodes::TooSmall->value, $message ?? 'must be positive', ['actual' => $v]);
            }
            return Result::ok($v);
        }));
    }

    public function scale(int $maxScale, ?string $message = null): static
    {
        if ($maxScale < 0) {
            throw new \InvalidArgumentException('maxScale must be non-negative');
        }
        return new static($this->chain(function (float $v, Path $p) use ($maxScale, $message): Result {
            // Use rtrim on the full precision string representation to avoid number_format
            // rounding artefacts with very small or very large floats.
            $str = rtrim(sprintf('%.14F', $v), '0');
            $parts = explode('.', $str);
            $actualScale = isset($parts[1]) ? strlen($parts[1]) : 0;
            if ($actualScale > $maxScale) {
                return Result::fail(
                    $p,
                    ErrorCodes::InvalidScale->value,
                    $message ?? "must have at most {$maxScale} decimal places",
                    ['maxScale' => $maxScale, 'actual' => $actualScale],
                );
            }
            return Result::ok($v);
        }));
    }

    /**
     * @return Decoder<I, float>
     */
    private function chain(callable $constraint): Decoder
    {
        return \Raoh\CallableDecoder::of(
            fn (mixed $in, ?Path $path = null): Result => $this->decode($in, $path)
                ->flatMap(fn (float $v): Result => $constraint($v, $path ?? Path::root())),
        );
    }
}
