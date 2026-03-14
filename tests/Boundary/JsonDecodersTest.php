<?php

declare(strict_types=1);

namespace Raoh\Tests\Boundary;

use PHPUnit\Framework\TestCase;
use Raoh\Err;
use Raoh\Ok;

use function Raoh\Boundary\Json\combine;
use function Raoh\Boundary\Json\field;
use function Raoh\Boundary\Json\from_json;
use function Raoh\Boundary\Json\int_;
use function Raoh\Boundary\Json\string_;

class JsonDecodersTest extends TestCase
{
    public function testFromJsonValid(): void
    {
        $dec = from_json(combine(
            field('email', string_()->email()),
            field('age',   int_()->range(0, 150)),
        )->map(fn ($email, $age) => ['email' => $email, 'age' => $age]));

        $r = $dec->decode('{"email":"user@example.com","age":25}');
        $this->assertInstanceOf(Ok::class, $r);
        $this->assertSame('user@example.com', $r->value['email']);
    }

    public function testFromJsonInvalidJson(): void
    {
        $dec = from_json(combine(
            field('email', string_()),
        )->map(fn ($e) => $e));

        $r = $dec->decode('{invalid-json}');
        $this->assertInstanceOf(Err::class, $r);
        $this->assertSame('invalid_format', $r->issues->toArray()[0]->code);
    }

    public function testFromJsonValidationErrors(): void
    {
        $dec = from_json(combine(
            field('email', string_()->email()),
            field('age',   int_()->range(0, 150)),
        )->map(fn ($email, $age) => ['email' => $email, 'age' => $age]));

        $r = $dec->decode('{"email":"bad","age":999}');
        $this->assertInstanceOf(Err::class, $r);
        $this->assertCount(2, $r->issues->toArray());
    }

    public function testFromJsonNotAString(): void
    {
        $dec = from_json(string_()->map(fn ($s) => $s));
        $r = $dec->decode(42);
        $this->assertInstanceOf(Err::class, $r);
        $this->assertSame('type_mismatch', $r->issues->toArray()[0]->code);
    }

    public function testFromJsonDepthExceeded(): void
    {
        // Depth 1 cannot decode nested objects
        $dec = from_json(
            combine(field('a', string_()))->map(fn ($a) => $a),
            depth: 1,
        );
        $r = $dec->decode('{"a":{"b":1}}');
        $this->assertInstanceOf(Err::class, $r);
    }

    public function testFromJsonInvalidDepthThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        from_json(string_()->map(fn ($s) => $s), depth: 0);
    }
}
