# Koriym.EnvJson

[Japanese](./README.ja.md)

Use JSON instead of INI files to set environment variables.
Validation by [JSON schema](https://json-schema.org/) is performed on ENV values as well as JSON.

## Installation

    composer require koriym/env-json

## Usage

Specify the directory of the `env.schema.json` schema file to `export()`.

If environment variables are already set, such as in a production environment, they are validated by `env.schema.json` to ensure that they are correct.

Otherwise, `env.json` or `env.dist.json` will be loaded, validated, and exported as environment variable values.


```php
(new EnvJson)->export($dir);
```

$dir/env.json

```json
{
    "$schema": "./env.schema.json",
    "FOO": "foo1",
    "BAR": "bar1"
}
```

$dir/env.schema.json

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

## Convert ini file

JSON and its JSON schema file are generated from the env(ini) file with `ini2json`.

```
. /vendor/bin/ini2json .env
```
