<?php

declare(strict_types=1);

namespace Raoh\Tests\Boundary\Array_;

use PHPUnit\Framework\TestCase;
use Raoh\Tests\Fixtures\Color;
use Raoh\Tests\Fixtures\Status;

use function Raoh\Boundary\Array_\Encode\bool_;
use function Raoh\Boundary\Array_\Encode\date_;
use function Raoh\Boundary\Array_\Encode\date_time_;
use function Raoh\Boundary\Array_\Encode\enum_of;
use function Raoh\Boundary\Array_\Encode\float_;
use function Raoh\Boundary\Array_\Encode\int_;
use function Raoh\Boundary\Array_\Encode\list_;
use function Raoh\Boundary\Array_\Encode\nested;
use function Raoh\Boundary\Array_\Encode\nullable;
use function Raoh\Boundary\Array_\Encode\object_;
use function Raoh\Boundary\Array_\Encode\property;
use function Raoh\Boundary\Array_\Encode\string_;
use function Raoh\Boundary\Array_\Encode\with_default;

class EncodersTest extends TestCase
{
    public function testStringEncoder(): void
    {
        $enc = string_();
        $this->assertSame('hello', $enc->encode('hello'));
    }

    public function testIntEncoder(): void
    {
        $enc = int_();
        $this->assertSame(42, $enc->encode(42));
    }

    public function testFloatEncoder(): void
    {
        $enc = float_();
        $this->assertSame(3.14, $enc->encode(3.14));
    }

    public function testBoolEncoder(): void
    {
        $enc = bool_();
        $this->assertTrue($enc->encode(true));
        $this->assertFalse($enc->encode(false));
    }

    public function testDateEncoder(): void
    {
        $enc = date_();
        $date = new \DateTimeImmutable('2026-04-17');
        $this->assertSame('2026-04-17', $enc->encode($date));
    }

    public function testDateTimeEncoder(): void
    {
        $enc = date_time_();
        $dt = new \DateTimeImmutable('2026-04-17T12:34:56+00:00');
        $this->assertSame('2026-04-17T12:34:56+00:00', $enc->encode($dt));
    }

    public function testEnumOfEncodesName(): void
    {
        $enc = enum_of();
        $this->assertSame('Red', $enc->encode(Color::Red));
    }

    public function testEnumOfEncodesBackedValue(): void
    {
        $enc = enum_of();
        $this->assertSame('active', $enc->encode(Status::Active));
    }

    public function testNullableWithValue(): void
    {
        $enc = nullable(string_());
        $this->assertSame('foo', $enc->encode('foo'));
    }

    public function testNullableWithNull(): void
    {
        $enc = nullable(string_());
        $this->assertNull($enc->encode(null));
    }

    public function testWithDefaultReplacesNull(): void
    {
        $enc = with_default(string_(), 'default');
        $this->assertSame('default', $enc->encode(null));
        $this->assertSame('given', $enc->encode('given'));
    }

    public function testObjectEncoder(): void
    {
        $enc = object_(
            property('name', fn(array $r): string => $r['name'], string_()),
            property('age',  fn(array $r): int   => $r['age'],  int_()),
        );

        $result = $enc->encode(['name' => 'Alice', 'age' => 30]);
        $this->assertSame(['name' => 'Alice', 'age' => 30], $result);
    }

    public function testNestedObjectEncoder(): void
    {
        $addressEnc = object_(
            property('city', fn(array $a): string => $a['city'], string_()),
        );
        $enc = object_(
            property('name',    fn(array $r): string => $r['name'],    string_()),
            property('address', fn(array $r): array  => $r['address'], nested($addressEnc)),
        );

        $result = $enc->encode([
            'name'    => 'Bob',
            'address' => ['city' => 'Tokyo'],
        ]);
        $this->assertSame(['name' => 'Bob', 'address' => ['city' => 'Tokyo']], $result);
    }

    public function testListEncoder(): void
    {
        $enc = list_(int_());
        $this->assertSame([1, 2, 3], $enc->encode([1, 2, 3]));
    }

    public function testContramapOnObjectEncoder(): void
    {
        $enc = int_()->contramap(fn(object $o): int => $o->id);

        $obj = new \stdClass();
        $obj->id = 7;
        $this->assertSame(7, $enc->encode($obj));
    }
}
