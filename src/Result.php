<?php

declare(strict_types=1);

namespace Raoh;

/**
 * @template T
 */
abstract readonly class Result
{
    /**
     * @template U
     * @param U $value
     * @return Ok<U>
     */
    public static function ok(mixed $value): Ok
    {
        return new Ok($value);
    }

    /**
     * @return Err<never>
     */
    public static function err(Issues $issues): Err
    {
        return new Err($issues);
    }

    /**
     * @return Err<never>
     * @param array<string, mixed> $meta
     */
    public static function fail(
        Path $path,
        string $code,
        string $message,
        array $meta = [],
    ): Err {
        return new Err(Issues::empty()->add(Issue::of($path, $code, $message, $meta)));
    }

    /**
     * @return Err<never>
     * @param array<string, mixed> $meta
     */
    public static function failAtRoot(string $code, string $message, array $meta = []): Err
    {
        return self::fail(Path::root(), $code, $message, $meta);
    }

    public function isOk(): bool
    {
        return $this instanceof Ok;
    }

    public function isErr(): bool
    {
        return $this instanceof Err;
    }

    /**
     * @template U
     * @param callable(T): U $f
     * @return Result<U>
     */
    public function map(callable $f): Result
    {
        if ($this instanceof Ok) {
            return Result::ok($f($this->value));
        }
        /** @var Err<U> */
        return $this;
    }

    /**
     * @template U
     * @param callable(T): Result<U> $f
     * @return Result<U>
     */
    public function flatMap(callable $f): Result
    {
        if ($this instanceof Ok) {
            return $f($this->value);
        }
        return $this;
    }

    /**
     * @template R
     * @param callable(T): R $onOk
     * @param callable(Issues): R $onErr
     * @return R
     */
    public function fold(callable $onOk, callable $onErr): mixed
    {
        if ($this instanceof Ok) {
            return $onOk($this->value);
        }
        /** @var Err<T> $this */
        return $onErr($this->issues);
    }

    /**
     * @return T
     * @throws \RuntimeException
     */
    public function getOrThrow(): mixed
    {
        return $this->fold(
            fn ($v) => $v,
            fn (Issues $issues) => throw new \RuntimeException(
                'Decode failed: ' . implode('; ', array_map(
                    fn (Issue $i) => ($i->path->toJsonPointer() ?: '/') . ': ' . $i->message,
                    $issues->toArray(),
                )),
            ),
        );
    }

    /**
     * @param callable(Issues): \Throwable $mapper
     * @return T
     */
    public function orElseThrow(callable $mapper): mixed
    {
        return $this->fold(
            fn ($v) => $v,
            fn (Issues $issues) => throw $mapper($issues),
        );
    }

    /**
     * @template A
     * @template B
     * @template C
     * @param Result<A> $ra
     * @param Result<B> $rb
     * @param callable(A, B): C $f
     * @return Result<C>
     */
    public static function map2(Result $ra, Result $rb, callable $f): Result
    {
        if ($ra instanceof Ok && $rb instanceof Ok) {
            return Result::ok($f($ra->value, $rb->value));
        }
        $issues = Issues::empty();
        if ($ra instanceof Err) {
            $issues = $issues->merge($ra->issues);
        }
        if ($rb instanceof Err) {
            $issues = $issues->merge($rb->issues);
        }
        return Result::err($issues);
    }

    /**
     * @template I
     * @template U
     * @param list<I> $items
     * @param callable(I, Path): Result<U> $f
     * @return Result<list<U>>
     */
    public static function traverse(array $items, callable $f, ?Path $basePath = null): Result
    {
        $basePath ??= Path::root();
        $accumulated = Issues::empty();
        $values = [];
        foreach ($items as $i => $item) {
            $itemPath = $basePath->append((string) $i);
            $r = $f($item, $itemPath);
            if ($r instanceof Ok) {
                $values[] = $r->value;
            } else {
                /** @var Err<U> $r */
                $accumulated = $accumulated->merge($r->issues);
            }
        }
        return $accumulated->isEmpty()
            ? Result::ok($values)
            : Result::err($accumulated);
    }
}
