# Koriym.EnvJson

Write your env file in JSON, with schema completion instead of copy-paste for env keys, and validate it with standard Json Schema rules instead of the library's own rules.

## Installation

    composer require koriym/env-json

## Usage

`load()` the directory containing the JsonSchema file.

If environment variables are already set, they are validated in `env.schema.json`. If not, `env.json` or `env.dist.json` is loaded, validated, and exported to the environment variable value.

```php
(new EnvJson)->export(__DIR__);
```

## env.json

```json
{
    "$schema": "./env.schema.json",
    "FOO": "foo1",
    "BAR": "bar1"
}
```

## env.schema.json

```json
{
    "$schema": "http://json-schema.org/draft-07/schema#",
    "type": "object",
    "required": [
        "FOO", "BAR"
    ],
    "properties": {
        "FOO": {
            "description": "FOO's value"
        },
        "BAR": {
            "description": "BAR's value",
            "examples": ["bar1", "bar-1"],
            "minLength": 3
        }
    }
}
```
