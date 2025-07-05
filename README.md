# Koriym.EnvJson

[![Continuous Integration](https://github.com/koriym/Koriym.EnvJson/actions/workflows/continuous-integration.yml/badge.svg?branch=1.x)](https://github.com/koriym/Koriym.EnvJson/actions/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/koriym/Koriym.EnvJson/graph/badge.svg?token=QTQenpijgq)](https://codecov.io/gh/koriym/Koriym.EnvJson)
[![Type Coverage](https://shepherd.dev/github/koriym/Koriym.EnvJson/coverage.svg)](https://shepherd.dev/github/koriym/Koriym.EnvJson)

A modern approach to environment variables using JSON instead of `.env` files, with built-in validation via JSON Schema.

- Documentation: [English](https://koriym.github.io/Koriym.EnvJson/) | [Japanese](https://koriym.github.io/Koriym.EnvJson/README.ja)

<img src="https://koriym.github.io/Koriym.EnvJson/images/story/ja1.jpg" width="600px" alt="env.json logo">

## Installation

```bash
composer require koriym/env-json
```

## Basic Usage

```php
// Load and validate environment variables
$env = (new EnvJson())->load(__DIR__);

// Access variables
echo $env->DATABASE_URL;
echo getenv('DATABASE_URL');
```

## Configuration Files

### env.schema.json
```json
{
    "$schema": "http://json-schema.org/draft-07/schema#",
    "type": "object",
    "required": ["DATABASE_URL", "API_KEY"],
    "properties": {
        "DATABASE_URL": {
            "description": "Database connection string",
            "pattern": "^mysql://.*"
        },
        "API_KEY": {
            "description": "API authentication key",
            "minLength": 32
        },
        "DEBUG_MODE": {
            "description": "Enable debug output",
            "enum": ["true", "false"],
            "default": "false"
        }
    }
}
```

### env.json
```json
{
    "$schema": "./env.schema.json",
    "DATABASE_URL": "mysql://user:pass@localhost/mydb",
    "API_KEY": "1234567890abcdef1234567890abcdef",
    "DEBUG_MODE": "true"
}
```

## ⚠️ Important: Environment variables are always strings

Do not use `"type": "boolean"` or `"type": "integer"` in your schema. Use `"enum": ["true", "false"]` for booleans and `"pattern": "^[0-9]+$"` for numbers.

## Converting from .env

```bash
bin/ini2json .env
```

This generates both `env.schema.json` and `env.json` files.

## CLI Tool

```bash
# Load variables into current shell
source <(bin/envjson)

# Specify custom directory
source <(bin/envjson -d ./config)

# Output formats
bin/envjson --output=shell   # export FOO="bar"
bin/envjson --output=fpm     # env[FOO] = "bar"
bin/envjson --output=ini     # FOO="bar"
```

## Try the Demo

```bash
# Run all demos
php demo/run.php

# Or run individual demos
php demo/env-json-1/run.php     # Basic usage
php demo/convert/run.php         # Convert .env to JSON
php demo/error-handling/run.php  # Error handling examples
php demo/validation/run.php      # Schema validation examples
```

## Links

- [GitHub](https://github.com/koriym/Koriym.EnvJson)
- [Packagist](https://packagist.org/packages/koriym/env-json)
- [Full Documentation](https://koriym.github.io/Koriym.EnvJson/)

*Configuration deserves more than plaintext. Structure it. Validate it. Understand it—with env.json!*
