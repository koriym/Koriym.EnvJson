<?php

declare(strict_types=1);

namespace Koriym\EnvJson;

use PHPUnit\Framework\TestCase;

class JsonTest extends TestCase
{
    public function testJson(): void
    {
        $envFile = __DIR__ . '/Fake/.env';
        $json = new Json($envFile);
        $this->assertJsonStringEqualsJsonString('{
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
        $this->assertSame('{
    "$schema": "./env.schema.json",
    "FOO": "foo1",
    "BAR": "bar1",
    "API": "http://example.com"
}
', $json->data);
    }
}
