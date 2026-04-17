<?php

declare(strict_types=1);

namespace Raoh\Tests;

use PHPUnit\Framework\TestCase;
use Raoh\CallableEncoder;

class EncoderTest extends TestCase
{
    public function testEncodeReturnsValue(): void
    {
        $enc = CallableEncoder::of(fn(string $v): string => strtoupper($v));
        $this->assertSame('HELLO', $enc->encode('hello'));
    }

    public function testContramapPreProcessesInput(): void
    {
        $enc = CallableEncoder::of(fn(int $v): string => (string) $v);
        $wrapped = $enc->contramap(fn(object $o): int => $o->id);

        $obj = new \stdClass();
        $obj->id = 42;
        $this->assertSame('42', $wrapped->encode($obj));
    }

    public function testAndThenPostProcessesOutput(): void
    {
        $enc = CallableEncoder::of(fn(int $v): string => (string) $v);
        $chained = $enc->andThen(CallableEncoder::of(fn(string $s): string => 'id:' . $s));

        $this->assertSame('id:99', $chained->encode(99));
    }
}
