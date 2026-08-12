<?php

declare(strict_types=1);

namespace Tests;

use Aazsamir\Plap\ArgsParser;
use Tests\Fixtures\Simple;
use Tests\Fixtures\SomeEnum;
use Tests\Fixtures\Typed;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ArgsParser::class)]
final class ArgsParserTest extends TestCase
{
    public function testParsesRequiredValuesForSimpleFixture(): void
    {
        $parsed = ArgsParser::parse([
            'script.php',
            '--name=Alice',
            '--age',
            '42',
            '--active',
        ], Simple::class);

        $this->assertSame('Alice', $parsed->name);
        $this->assertSame(42, $parsed->age);
        $this->assertTrue($parsed->active);
    }

    public function testParsesBooleanNegationAndPositionalValuesForTypedFixture(): void
    {
        $parsed = ArgsParser::parse([
            'script.php',
            'release',
            '--name',
            'Bob',
            '--age',
            '31',
            '--no-active',
            '--enum=bar',
            '--tags=tag1,tag2,tag3',
        ], Typed::class);

        $this->assertSame('release', $parsed->tag);
        $this->assertSame('Bob', $parsed->name);
        $this->assertSame(31, $parsed->age);
        $this->assertFalse($parsed->active);
        $this->assertSame(SomeEnum::BAR, $parsed->enum);
        $this->assertSame(['tag1', 'tag2', 'tag3'], $parsed->tags);
    }

    public function testThrowsWhenRequiredArgumentsAreMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ArgsParser::parse([
            'script.php',
            '--name',
            'Alice',
            '--active',
        ], Simple::class);
    }
}
