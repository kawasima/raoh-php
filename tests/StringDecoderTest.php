<?php

declare(strict_types=1);

namespace Raoh\Tests;

use PHPUnit\Framework\TestCase;
use Raoh\Err;
use Raoh\Ok;

use function Raoh\Boundary\Array_\string_;

class StringDecoderTest extends TestCase
{
    public function testDecodeString(): void
    {
        $r = string_()->decode('hello');
        $this->assertInstanceOf(Ok::class, $r);
        $this->assertSame('hello', $r->value);
    }

    public function testRequiredOnNull(): void
    {
        $r = string_()->decode(null);
        $this->assertInstanceOf(Err::class, $r);
        $this->assertSame('required', $r->issues->toArray()[0]->code);
    }

    public function testTypeMismatch(): void
    {
        $r = string_()->decode(42);
        $this->assertInstanceOf(Err::class, $r);
        $this->assertSame('type_mismatch', $r->issues->toArray()[0]->code);
    }

    public function testTrim(): void
    {
        $r = string_()->trim()->decode('  hello  ');
        $this->assertInstanceOf(Ok::class, $r);
        $this->assertSame('hello', $r->value);
    }

    public function testToLowerCase(): void
    {
        $r = string_()->toLowerCase()->decode('HELLO');
        $this->assertInstanceOf(Ok::class, $r);
        $this->assertSame('hello', $r->value);
    }

    public function testNonBlankPass(): void
    {
        $r = string_()->nonBlank()->decode('hello');
        $this->assertInstanceOf(Ok::class, $r);
    }

    public function testNonBlankFail(): void
    {
        $r = string_()->nonBlank()->decode('   ');
        $this->assertInstanceOf(Err::class, $r);
        $this->assertSame('blank', $r->issues->toArray()[0]->code);
    }

    public function testMinLength(): void
    {
        $r = string_()->minLength(3)->decode('ab');
        $this->assertInstanceOf(Err::class, $r);
        $this->assertSame('too_short', $r->issues->toArray()[0]->code);
    }

    public function testMaxLength(): void
    {
        $r = string_()->maxLength(5)->decode('toolong');
        $this->assertInstanceOf(Err::class, $r);
        $this->assertSame('too_long', $r->issues->toArray()[0]->code);
    }

    public function testEmailValid(): void
    {
        $r = string_()->email()->decode('user@example.com');
        $this->assertInstanceOf(Ok::class, $r);
    }

    public function testEmailInvalid(): void
    {
        $r = string_()->email()->decode('not-an-email');
        $this->assertInstanceOf(Err::class, $r);
        $this->assertSame('invalid_format', $r->issues->toArray()[0]->code);
    }

    public function testChaining(): void
    {
        $dec = string_()->trim()->toLowerCase()->email();
        $r = $dec->decode('  USER@EXAMPLE.COM  ');
        $this->assertInstanceOf(Ok::class, $r);
        $this->assertSame('user@example.com', $r->value);
    }

    public function testPattern(): void
    {
        $r = string_()->pattern('/^\d{3}-\d{4}$/')->decode('123-4567');
        $this->assertInstanceOf(Ok::class, $r);

        $r2 = string_()->pattern('/^\d{3}-\d{4}$/')->decode('invalid');
        $this->assertInstanceOf(Err::class, $r2);
    }

    public function testUuid(): void
    {
        $r = string_()->uuid()->decode('550e8400-e29b-41d4-a716-446655440000');
        $this->assertInstanceOf(Ok::class, $r);

        $r2 = string_()->uuid()->decode('not-a-uuid');
        $this->assertInstanceOf(Err::class, $r2);
    }

    public function testToInt(): void
    {
        $r = string_()->toInt()->decode('42');
        $this->assertInstanceOf(Ok::class, $r);
        $this->assertSame(42, $r->value);
    }

    public function testToIntFail(): void
    {
        $r = string_()->toInt()->decode('12.5');
        $this->assertInstanceOf(Err::class, $r);
        $this->assertSame('type_mismatch', $r->issues->toArray()[0]->code);
    }

    public function testMap(): void
    {
        $r = string_()->map(fn ($s) => strtoupper($s))->decode('hello');
        $this->assertInstanceOf(Ok::class, $r);
        $this->assertSame('HELLO', $r->value);
    }

    public function testPatternInvalidRegexThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        string_()->pattern('/[invalid/');
    }

    public function testUrlValid(): void
    {
        $this->assertInstanceOf(Ok::class, string_()->url()->decode('https://example.com'));
        $this->assertInstanceOf(Ok::class, string_()->url()->decode('http://example.com:8080/path'));
    }

    public function testUrlInvalidScheme(): void
    {
        $r = string_()->url()->decode('ftp://example.com');
        $this->assertInstanceOf(Err::class, $r);
        $this->assertSame('invalid_format', $r->issues->toArray()[0]->code);
    }

    public function testUrlPortOutOfRange(): void
    {
        $r = string_()->url()->decode('http://example.com:99999');
        $this->assertInstanceOf(Err::class, $r);
        $this->assertSame('invalid_format', $r->issues->toArray()[0]->code);
    }

    public function testUrlPortBoundary(): void
    {
        $this->assertInstanceOf(Ok::class, string_()->url()->decode('http://example.com:1'));
        $this->assertInstanceOf(Ok::class, string_()->url()->decode('http://example.com:65535'));
    }
}
