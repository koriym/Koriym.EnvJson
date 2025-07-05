# EnvJson Demo

This demo showcases the basic usage of the EnvJson library through various examples.

## Demo Structure

The demo consists of multiple examples that demonstrate different aspects of the EnvJson library:

- `convert/` - Demonstrates conversion from .env to JSON format using the ini2json binary
- `env-json-1/` - Basic EnvJson usage example
- `env-json-2/` - Advanced EnvJson usage example  
- `env/` - Environment variable handling and priority example
- `ini-json/` - Direct usage of IniJson class for INI to JSON conversion
- `error-handling/` - Error handling examples for invalid files and formats
- `validation/` - JSON schema validation examples and common validation errors
- `envjson-cli/` - Command line tool usage examples

## Running the Demo

### Run All Demos
```bash
php demo/run.php
```

### Run Individual Demos
```bash
# Basic EnvJson usage
php demo/env-json-1/run.php

# Advanced EnvJson usage
php demo/env-json-2/run.php

# Environment variable priority
php demo/env/run.php

# Convert .env to JSON
php demo/convert/run.php

# Direct IniJson class usage
php demo/ini-json/run.php

# Error handling examples
php demo/error-handling/run.php

# Schema validation examples
php demo/validation/run.php

# CLI tool usage examples  
php demo/envjson-cli/run.php
```

## Demo Details

### convert/
Shows how to convert existing .env files to JSON format using the `ini2json` binary tool.

### env-json-1/ & env-json-2/
Basic examples of loading environment variables from env.json files.

### env/
Demonstrates how environment variables already set in the system take priority over those in JSON files.

### ini-json/ (NEW)
Shows direct usage of the IniJson class to convert INI files to JSON and generate corresponding JSON schemas.

### error-handling/ (NEW)
Demonstrates proper error handling for:
- Invalid INI file formats
- Non-existent files
- Invalid JSON syntax

### validation/ (NEW)
Shows JSON schema validation in action with examples of:
- Valid configuration that passes validation
- Invalid configurations that fail validation
- Common validation errors and how to fix them

### envjson-cli/ (NEW)
Demonstrates the bin/envjson command line tool:
- Different output formats (shell, PHP-FPM, INI)
- Command line options and usage examples
- Integration with shell environments
