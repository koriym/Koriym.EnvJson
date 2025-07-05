# Koriym.EnvJson

<img src="https://koriym.github.io/Koriym.EnvJson/images/envjson.jpg" width="400px" alt="env.json logo">


[Japanese](./README.ja.md)

A modern approach to environment variables using JSON instead of `.env` files, with built-in validation via [JSON schema](https://json-schema.org/).

## Features

- **Validated environment variables** with JSON schema validation
- **Better documentation** through schema descriptions and constraints
- **Conversion tools** to migrate from `.env` to JSON format
- **CLI utilities** for shell integration and different output formats
- **Fallback mechanism** for development environments

## Installation

```bash
composer require koriym/env-json
```

## Basic Usage

```php
// Load and validate environment variables
$env = (new EnvJson())->load(__DIR__);

// Access variables as object properties
echo $env->DATABASE_URL;

// Or use traditional getenv()
echo getenv('DATABASE_URL');
```

## Configuration Files

### JSON Schema (env.schema.json)

Define your environment variables with types, descriptions, and constraints:

```json
{
    "$schema": "http://json-schema.org/draft-07/schema#",
    "type": "object",
    "required": [
        "DATABASE_URL", "API_KEY"
    ],
    "properties": {
        "DATABASE_URL": {
            "description": "Connection string for the database",
            "pattern": "^mysql://.*"
        },
        "API_KEY": {
            "description": "Authentication key for external API",
            "minLength": 32
        },
        "DEBUG_MODE": {
            "description": "Enable debug output (true/false)",
            "enum": ["true", "false"],
            "default": "false"
        },
        "PORT": {
            "description": "Server port number",
            "pattern": "^[0-9]+$",
            "default": "3000"
        }
    }
}
```

### Environment File (env.json)

Your actual configuration values:

```json
{
    "$schema": "./env.schema.json",
    "DATABASE_URL": "mysql://user:pass@localhost/mydb",
    "API_KEY": "1234567890abcdef1234567890abcdef",
    "DEBUG_MODE": "true",
    "PORT": "8080"
}
```

## ⚠️ Critical: Environment Variable Type Constraints

Environment variables are always treated as strings. Using non-string types in your JSON schema will cause validation errors.

### ❌ These Will Cause Validation Errors

```json
{
    "DEBUG_MODE": {
        "type": "boolean",
        "default": false
    },
    "PORT": {
        "type": "integer",
        "default": 3000
    },
    "TIMEOUT": {
        "type": "number",
        "default": 30.5
    }
}
```

**Error message**: The value will be a string (e.g., "3000"), but the schema expects an integer, causing validation to fail.

### ✅ Correct Approach

```json
{
    "DEBUG_MODE": {
        "description": "Enable debug output (true/false)",
        "enum": ["true", "false"],
        "default": "false"
    },
    "PORT": {
        "description": "Server port number",
        "pattern": "^[0-9]+$",
        "default": "3000"
    },
    "TIMEOUT": {
        "description": "Timeout in seconds",
        "pattern": "^[0-9]+(\\.[0-9]+)?$",
        "default": "30.5"
    }
}
```

### Recommended Patterns

**Boolean values:**
```json
"FEATURE_ENABLED": {
    "enum": ["true", "false"],
    "default": "false"
}
```

**Numeric values:**
```json
"TIMEOUT": {
    "pattern": "^[0-9]+$",
    "default": "30"
}
```

**Enum values:**
```json
"LOG_LEVEL": {
    "enum": ["debug", "info", "warning", "error"],
    "default": "info"
}
```

## Workflow & Best Practices

### Development Environment

1. **Schema creation**: Define `env.schema.json` with all required variables, patterns, and constraints
2. **Default values**: Create `env.dist.json` with default/sample values that can be shared with the team
3. **Local overrides**: Create `env.json` with your specific local values (add to `.gitignore`)
4. **Loading process**:
    - EnvJson first tries to validate existing environment variables
    - If validation fails, it loads `env.json` if present
    - If `env.json` is not found, it falls back to `env.dist.json`

### Production Environment

1. **CI/CD setup**:
    - Remove `env.dist.json` during deployment (not needed in production)
    - Do not include `env.json` (should be in `.gitignore`)
2. **Configuration**: Set all environment variables directly in your production environment
3. **Validation**: EnvJson validates that all required variables are present and valid

## Converting from .env

Convert your existing `.env` files to JSON format:

```bash
bin/ini2json .env
```

This generates both `env.schema.json` and `env.dist.json` files.

## Command Line Tool: envjson

The `envjson` command line tool helps you integrate with various environments:

```bash
# Load variables into current shell
source <(bin/envjson)

# Specify custom directory
source <(bin/envjson -d ./config)

# Output in PHP-FPM format: env[FOO] = "foo1"
bin/envjson -d ./config -o fpm > .env.fpm

# Output in INI format: FOO="foo1"
bin/envjson -d ./config -o ini > env.ini

# Output in shell format: export FOO="foo1"
bin/envjson -d ./config -o shell > env.sh
```

### Options

```
  -d --dir=DIR     Directory containing env files (default: current directory)
  -f --file=FILE   JSON file name to load (default: env.json)
  -o --output=FMT  Output format: shell, fpm, ini (default: shell)
  -v --verbose     Show detailed messages
  -q --quiet       Suppress all warning messages
  -h --help        Display help message
```

## Why JSON instead of .env?

- **Validation**: Validate types and constraints before your application starts
- **Better documentation** through schema descriptions and constraints
- **IDE Support**: Better tooling with JSON schema validation in editors
- **Constrained Data**: Json Schema's constraints for validation

## Link

- [GitHub](https://github.com/koriym/Koriym.EnvJson)
- [Packagist](https://packagist.org/packages/koriym/env-json)
