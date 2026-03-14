<?php

declare(strict_types=1);

namespace Raoh\Tests;

use PHPUnit\Framework\TestCase;
use Raoh\Err;
use Raoh\Ok;

use function Raoh\Boundary\Array_\combine;
use function Raoh\Boundary\Array_\field;
use function Raoh\Boundary\Array_\int_;
use function Raoh\Boundary\Array_\string_;

class CombineTest extends TestCase
{
    public function testBothFieldsSucceed(): void
    {
        $dec = combine(
            field('email', string_()->email()),
            field('age',   int_()->range(0, 150)),
        )->map(fn ($email, $age) => ['email' => $email, 'age' => $age]);

        $r = $dec->decode(['email' => 'user@example.com', 'age' => 25]);
        $this->assertInstanceOf(Ok::class, $r);
        $this->assertSame('user@example.com', $r->value['email']);
        $this->assertSame(25, $r->value['age']);
    }

    public function testBothFieldsFailAccumulatesErrors(): void
    {
        $dec = combine(
            field('email', string_()->email()),
            field('age',   int_()->range(0, 150)),
        )->map(fn ($email, $age) => ['email' => $email, 'age' => $age]);

        $r = $dec->decode(['email' => 'not-an-email', 'age' => 999]);
        $this->assertInstanceOf(Err::class, $r);
        $this->assertCount(2, $r->issues->toArray());
        $this->assertSame('/email', $r->issues->toArray()[0]->path->toJsonPointer());
        $this->assertSame('/age', $r->issues->toArray()[1]->path->toJsonPointer());
    }

    public function testMissingRequiredField(): void
    {
        $dec = combine(
            field('email', string_()),
            field('age',   int_()),
        )->map(fn ($e, $a) => compact('e', 'a'));

        $r = $dec->decode(['email' => 'x@x.com']);
        $this->assertInstanceOf(Err::class, $r);
        $this->assertSame('/age', $r->issues->toArray()[0]->path->toJsonPointer());
    }

    public function testFlatMap(): void
    {
        $dec = combine(
            field('password',        string_()->minLength(8)),
            field('passwordConfirm', string_()),
        )->flatMap(function ($pw, $confirm) {
            if ($pw !== $confirm) {
                return \Raoh\Result::fail(\Raoh\Path::of('passwordConfirm'), 'invalid_value', 'passwords do not match');
            }
            return \Raoh\Result::ok(['password' => $pw]);
        });

        $ok = $dec->decode(['password' => 'secret123', 'passwordConfirm' => 'secret123']);
        $this->assertInstanceOf(Ok::class, $ok);

        $err = $dec->decode(['password' => 'secret123', 'passwordConfirm' => 'other']);
        $this->assertInstanceOf(Err::class, $err);
    }

    public function testStrictRejectsUnknownFields(): void
    {
        $dec = combine(
            field('name', string_()),
        )->strict(fn ($name) => ['name' => $name]);

        $r = $dec->decode(['name' => 'Alice', 'unknown' => 'extra']);
        $this->assertInstanceOf(Err::class, $r);
        $this->assertSame('unknown_field', $r->issues->toArray()[0]->code);
    }

    public function testConstructorClosure(): void
    {
        // PHP では new ClassName(...) の Closure 化は未サポートのため fn() を使う
        $dec = combine(
            field('x', int_()),
            field('y', int_()),
        )->map(fn ($x, $y) => new Point($x, $y));

        $r = $dec->decode(['x' => 3, 'y' => 4]);
        $this->assertInstanceOf(Ok::class, $r);
        $this->assertSame(3, $r->value->x);
        $this->assertSame(4, $r->value->y);
    }

    public function testStaticConstructorTrait(): void
    {
        // StaticConstructor trait を使えば ClassName::of(...) と書ける
        $dec = combine(
            field('x', int_()),
            field('y', int_()),
        )->map(PointWithTrait::of(...));

        $r = $dec->decode(['x' => 3, 'y' => 4]);
        $this->assertInstanceOf(Ok::class, $r);
        $this->assertSame(3, $r->value->x);
        $this->assertSame(4, $r->value->y);
    }
}

// Helper class for constructor reference test
class Point
{
    public function __construct(
        public readonly int $x,
        public readonly int $y,
    ) {
    }
}

class PointWithTrait
{
    use \Raoh\StaticConstructor;

    public function __construct(
        public readonly int $x,
        public readonly int $y,
    ) {
    }
}
