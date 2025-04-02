<?php

declare(strict_types=1);

namespace Koriym\EnvJson;

use PHPUnit\Framework\TestCase;

use function str_replace;

class JsonTest extends TestCase
{
    public function testJson(): void
    {
        $envFile = __DIR__ . '/Fake/.env';
        $json = new Json($envFile);
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
            "type": "string",
            "format": "uri"
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
}
