<?php

declare(strict_types=1);

namespace Raoh\Tests;

use PHPUnit\Framework\TestCase;
use Raoh\Err;
use Raoh\Ok;

use function Raoh\Boundary\Array_\int_;

class IntDecoderTest extends TestCase
{
    public function testDecodeInt(): void
    {
        $r = int_()->decode(42);
        $this->assertInstanceOf(Ok::class, $r);
        $this->assertSame(42, $r->value);
    }

    public function testDecodeNumericString(): void
    {
        $r = int_()->decode('42');
        $this->assertInstanceOf(Ok::class, $r);
        $this->assertSame(42, $r->value);
    }

    public function testRequiredOnNull(): void
    {
        $r = int_()->decode(null);
        $this->assertInstanceOf(Err::class, $r);
        $this->assertSame('required', $r->issues->toArray()[0]->code);
    }

    public function testMin(): void
    {
        $this->assertInstanceOf(Ok::class, int_()->min(0)->decode(0));
        $r = int_()->min(1)->decode(0);
        $this->assertInstanceOf(Err::class, $r);
        $this->assertSame('too_small', $r->issues->toArray()[0]->code);
    }

    public function testMax(): void
    {
        $this->assertInstanceOf(Ok::class, int_()->max(10)->decode(10));
        $r = int_()->max(10)->decode(11);
        $this->assertInstanceOf(Err::class, $r);
        $this->assertSame('too_big', $r->issues->toArray()[0]->code);
    }

    public function testRange(): void
    {
        $this->assertInstanceOf(Ok::class, int_()->range(0, 150)->decode(25));
        $r = int_()->range(0, 150)->decode(200);
        $this->assertInstanceOf(Err::class, $r);
        $this->assertSame('out_of_range', $r->issues->toArray()[0]->code);
    }

    public function testPositive(): void
    {
        $this->assertInstanceOf(Ok::class, int_()->positive()->decode(1));
        $this->assertInstanceOf(Err::class, int_()->positive()->decode(0));
        $this->assertInstanceOf(Err::class, int_()->positive()->decode(-1));
    }

    public function testMultipleOf(): void
    {
        $this->assertInstanceOf(Ok::class, int_()->multipleOf(3)->decode(9));
        $r = int_()->multipleOf(3)->decode(10);
        $this->assertInstanceOf(Err::class, $r);
        $this->assertSame('not_multiple_of', $r->issues->toArray()[0]->code);
    }

    public function testRangeInvertedThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        int_()->range(100, 50);
    }
}
