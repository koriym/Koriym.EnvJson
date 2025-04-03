<?php

declare(strict_types=1);

namespace Koriym\EnvJson;

use Koriym\EnvJson\Exception\InvalidIniFileException;
use PHPUnit\Framework\TestCase;

use function str_replace;

class JsonTest extends TestCase
{
    public function testJson(): void
    {
        $envFile = __DIR__ . '/Fake/.env';
        $json = new IniJson($envFile);
        $this->assertSameNormalized('{
    "$schema": "http://json-schema.org/draft-04/schema#",
    "type": "object",
    "required": [
        "API",
        "BAR",
        "FOO"
    ],
    "properties": {
        "FOO": {
            "type": "string"
        },
        "BAR": {
            "type": "string"
        },
        "API": {
            "type": "string"
        }
    }
}
', $json->schema);
        $this->assertSameNormalized('{
    "$schema": "./env.schema.json",
    "FOO": "foo1",
    "BAR": "bar1",
    "API": "http://example.com"
}
', $json->data);
    }

    private function assertSameNormalized(string $expected, string $actual, string $message = ''): void
    {
        $normalize = static fn (string $s): string => str_replace(["\r\n", "\r"], "\n", $s);
        $this->assertSame($normalize($expected), $normalize($actual), $message);
    }

    public function testNonExustsIniFile(): void
    {
        $this->expectException(InvalidIniFileException::class);
        $invalidIniFile = '__NON_EXISTENT__';
        // Attempting to parse a non-existent file will cause parse_ini_file to return false
        new IniJson($invalidIniFile);
    }

    public function testInvalidIniFile(): void
    {
        $this->expectException(InvalidIniFileException::class);
        $invalidIniFile = __DIR__ . '/Fake/invalid.ini';
        // Attempting to parse a non-existent file will cause parse_ini_file to return false
        new IniJson($invalidIniFile);
    }
}
