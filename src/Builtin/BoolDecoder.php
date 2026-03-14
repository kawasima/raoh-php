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
 * @implements Decoder<I, bool>
 */
final class BoolDecoder implements Decoder
{
    /** @use DecoderTrait<I, bool> */
    use DecoderTrait;

    /** @param Decoder<I, bool> $inner */
    public function __construct(private readonly Decoder $inner)
    {
    }

    public function decode(mixed $in, ?Path $path = null): Result
    {
        return $this->inner->decode($in, $path ?? Path::root());
    }

    public function isTrue(?string $message = null): static
    {
        return new static($this->chain(function (bool $v, Path $p) use ($message): Result {
            if (!$v) {
                return Result::fail($p, ErrorCodes::InvalidValue->value, $message ?? 'must be true');
            }
            return Result::ok($v);
        }));
    }

    public function isFalse(?string $message = null): static
    {
        return new static($this->chain(function (bool $v, Path $p) use ($message): Result {
            if ($v) {
                return Result::fail($p, ErrorCodes::InvalidValue->value, $message ?? 'must be false');
            }
            return Result::ok($v);
        }));
    }

    /**
     * @return Decoder<I, bool>
     */
    private function chain(callable $constraint): Decoder
    {
        return \Raoh\CallableDecoder::of(
            fn (mixed $in, ?Path $path = null): Result => $this->decode($in, $path)
                ->flatMap(fn (bool $v): Result => $constraint($v, $path ?? Path::root())),
        );
    }
}
