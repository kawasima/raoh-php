<?php

declare(strict_types=1);

namespace Raoh\Tests;

use PHPUnit\Framework\TestCase;
use Raoh\Path;

class PathTest extends TestCase
{
    public function testRootIsEmptyPointer(): void
    {
        $this->assertSame('', Path::root()->toJsonPointer());
    }

    public function testAppendSingleSegment(): void
    {
        $this->assertSame('/email', Path::root()->append('email')->toJsonPointer());
    }

    public function testAppendMultipleSegments(): void
    {
        $path = Path::root()->append('address')->append('city');
        $this->assertSame('/address/city', $path->toJsonPointer());
    }

    public function testSegments(): void
    {
        $path = Path::root()->append('a')->append('b')->append('c');
        $this->assertSame(['a', 'b', 'c'], $path->segments());
    }

    public function testRootHasEmptySegments(): void
    {
        $this->assertSame([], Path::root()->segments());
    }

    public function testAppendPath(): void
    {
        $base = Path::root()->append('user');
        $suffix = Path::root()->append('address')->append('city');
        $this->assertSame('/user/address/city', $base->appendPath($suffix)->toJsonPointer());
    }

    public function testOf(): void
    {
        $path = Path::of('foo', 'bar', 'baz');
        $this->assertSame('/foo/bar/baz', $path->toJsonPointer());
    }

    public function testJsonPointerEscaping(): void
    {
        $path = Path::root()->append('a~b')->append('c/d');
        $this->assertSame('/a~0b/c~1d', $path->toJsonPointer());
    }
}
