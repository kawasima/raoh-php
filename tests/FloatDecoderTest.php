<?php

declare(strict_types=1);

namespace Raoh\Tests;

use PHPUnit\Framework\TestCase;
use Raoh\Err;
use Raoh\Ok;

use function Raoh\Boundary\Array_\float_;

class FloatDecoderTest extends TestCase
{
    public function testDecodeFloat(): void
    {
        $r = float_()->decode(3.14);
        $this->assertInstanceOf(Ok::class, $r);
        $this->assertSame(3.14, $r->value);
    }

    public function testDecodeIntAsFloat(): void
    {
        $r = float_()->decode(42);
        $this->assertInstanceOf(Ok::class, $r);
        $this->assertSame(42.0, $r->value);
    }

    public function testDecodeNumericString(): void
    {
        $r = float_()->decode('1.23');
        $this->assertInstanceOf(Ok::class, $r);
        $this->assertSame(1.23, $r->value);
    }

    public function testRequiredOnNull(): void
    {
        $r = float_()->decode(null);
        $this->assertInstanceOf(Err::class, $r);
        $this->assertSame('required', $r->issues->toArray()[0]->code);
    }

    public function testMin(): void
    {
        $this->assertInstanceOf(Ok::class, float_()->min(0.0)->decode(0.0));
        $r = float_()->min(1.0)->decode(0.5);
        $this->assertInstanceOf(Err::class, $r);
        $this->assertSame('too_small', $r->issues->toArray()[0]->code);
    }

    public function testMax(): void
    {
        $this->assertInstanceOf(Ok::class, float_()->max(10.0)->decode(10.0));
        $r = float_()->max(10.0)->decode(10.1);
        $this->assertInstanceOf(Err::class, $r);
        $this->assertSame('too_big', $r->issues->toArray()[0]->code);
    }

    public function testRange(): void
    {
        $this->assertInstanceOf(Ok::class, float_()->range(0.0, 1.0)->decode(0.5));
        $r = float_()->range(0.0, 1.0)->decode(1.1);
        $this->assertInstanceOf(Err::class, $r);
        $this->assertSame('out_of_range', $r->issues->toArray()[0]->code);
    }

    public function testRangeInvertedThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        float_()->range(100.0, 50.0);
    }

    public function testPositive(): void
    {
        $this->assertInstanceOf(Ok::class, float_()->positive()->decode(0.1));
        $this->assertInstanceOf(Err::class, float_()->positive()->decode(0.0));
        $this->assertInstanceOf(Err::class, float_()->positive()->decode(-1.0));
    }

    // -------------------------------------------------------------------------
    // scale() tests
    // -------------------------------------------------------------------------

    public function testScalePass(): void
    {
        $this->assertInstanceOf(Ok::class, float_()->scale(2)->decode(1.23));
        $this->assertInstanceOf(Ok::class, float_()->scale(2)->decode(1.2));
        $this->assertInstanceOf(Ok::class, float_()->scale(2)->decode(1.0));
    }

    public function testScaleFail(): void
    {
        $r = float_()->scale(2)->decode(1.234);
        $this->assertInstanceOf(Err::class, $r);
        $this->assertSame('invalid_scale', $r->issues->toArray()[0]->code);
    }

    public function testScaleZero(): void
    {
        $this->assertInstanceOf(Ok::class, float_()->scale(0)->decode(5.0));
        $r = float_()->scale(0)->decode(5.1);
        $this->assertInstanceOf(Err::class, $r);
        $this->assertSame('invalid_scale', $r->issues->toArray()[0]->code);
    }

    public function testScaleVerySmallFloat(): void
    {
        // 0.1 cannot be represented exactly in IEEE 754, but its string form
        // via sprintf('%.14F') rounds correctly to 1 decimal place.
        $this->assertInstanceOf(Ok::class, float_()->scale(1)->decode(0.1));
    }

    public function testScaleNegativeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        float_()->scale(-1);
    }
}
