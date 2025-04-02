# Koriym.EnvJson

[Japanese](./README.ja.md)

Use JSON instead of  `.env` file to set environment variables.
Validation by [JSON schema](https://json-schema.org/) is performed on environment variables as well as JSON.

## Installation

    composer require koriym/env-json

## Usage

Specify the directory of the `env.schema.json` schema file to `load()`.

```php
$env = (new EnvJson())->load($dir);
assert($env instanceof stdClass);
// Environment variables can be accessed as properties or by getenv()
assert($env->FOO === 'foo1');
assert(getenv('FOO') === 'foo1');
```

1) If environment variables are already set, they are validated by `env.schema.json` to see if they are correct.
2) If not, `env.json` or `env.dist.json` is read, validated by `env.schema.json`, and exported as the environment variables.

`$dir/env.json` or `$dir/env.dist.json`

```json
{
    "$schema": "./env.schema.json",
    "FOO": "foo1",
    "BAR": "bar1"
}
```

`$dir/env.schema.json`

```json
{
    "$schema": "http://json-schema.org/draft-07/schema#",
    "type": "object",
    "required": [
        "FOO", "BAR"
    ],
    "properties": {
        "FOO": {
            "description": "Foo's value",
            "minLength": 3
        },
        "BAR": {
            "description": "Bar's value",
            "enum": ["bar1", "bar2"]
        }
    }
}
```

It can provide more appropriate documentation and constraints compared to `.env` files.

## Convert ini file

JSON and its schema file are generated from the `.env` file with `ini2json`.

```
. /vendor/bin/ini2json .env
```
