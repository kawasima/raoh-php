<?php

declare(strict_types=1);

namespace Raoh\Tests\Boundary\Json;

use PHPUnit\Framework\TestCase;

use function Raoh\Boundary\Array_\Encode\int_;
use function Raoh\Boundary\Array_\Encode\object_;
use function Raoh\Boundary\Array_\Encode\property;
use function Raoh\Boundary\Array_\Encode\string_;
use function Raoh\Boundary\Json\Encode\to_json;

class EncodersTest extends TestCase
{
    public function testToJsonEncodesPrimitiveObject(): void
    {
        $enc = object_(
            property('id',   fn(array $r): int    => $r['id'],   int_()),
            property('name', fn(array $r): string => $r['name'], string_()),
        );

        $json = to_json($enc)(['id' => 1, 'name' => 'Alice']);
        $this->assertSame('{"id":1,"name":"Alice"}', $json);
    }

    public function testToJsonReturnsString(): void
    {
        $enc = object_(
            property('x', fn(array $r): int => $r['x'], int_()),
        );

        $result = to_json($enc)(['x' => 42]);
        $this->assertIsString($result);
    }
}
