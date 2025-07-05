<?php

use Koriym\EnvJson\EnvJson;
use Koriym\EnvJson\Exception\InvalidEnvJsonException;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

echo "=== Schema Validation Demo ===" . PHP_EOL;
echo PHP_EOL;

echo "This demo shows how JSON schema validation works with env.json files." . PHP_EOL;
echo PHP_EOL;

// Clear any existing environment variables to ensure file validation
foreach (['DATABASE_URL', 'API_KEY', 'DEBUG_MODE', 'PORT'] as $var) {
    putenv($var);
}

// Demo 1: Valid env.json (should work)
echo "1. Testing valid env.json:" . PHP_EOL;
try {
    // Create a valid env.json file
    $validEnv = [
        '$schema' => './env.schema.json',
        'DATABASE_URL' => 'mysql://user:pass@localhost/mydb',
        'API_KEY' => '1234567890abcdef1234567890abcdef1234567890abcdef',
        'DEBUG_MODE' => 'true',
        'PORT' => '8080'
    ];

    file_put_contents(__DIR__ . '/env.json', json_encode($validEnv, JSON_PRETTY_PRINT));

    $envJson = new EnvJson();
    $env = $envJson->load(__DIR__);

    echo "✓ Valid env.json loaded successfully!" . PHP_EOL;
    echo "  DATABASE_URL: " . $env->DATABASE_URL . PHP_EOL;
    echo "  API_KEY: " . substr($env->API_KEY, 0, 10) . "..." . PHP_EOL;
    echo "  DEBUG_MODE: " . $env->DEBUG_MODE . PHP_EOL;
    echo "  PORT: " . $env->PORT . PHP_EOL;

} catch (InvalidEnvJsonException $e) {
    echo "✗ Unexpected error: " . $e->getMessage() . PHP_EOL;
} finally {
    @unlink(__DIR__ . '/env.json');
}
echo PHP_EOL;

// Demo 2: Invalid env.json (schema validation errors)
echo "2. Testing invalid env.json (schema validation errors):" . PHP_EOL;
try {
    // Create an invalid env.json file with validation errors
    $invalidEnv = [
        '$schema' => './env.schema.json',
        'DATABASE_URL' => 'postgresql://user:pass@localhost/mydb', // Wrong pattern
        'API_KEY' => 'short_key',                                   // Too short
        'DEBUG_MODE' => 'maybe',                                    // Invalid enum
        'PORT' => 'not_a_number'                                    // Invalid pattern
    ];

    file_put_contents(__DIR__ . '/env.json', json_encode($invalidEnv, JSON_PRETTY_PRINT));

    $envJson = new EnvJson();
    $env = $envJson->load(__DIR__);

    echo "✗ Unexpected success - should have failed validation!" . PHP_EOL;

} catch (InvalidEnvJsonException $e) {
    echo "✓ Caught expected validation error:" . PHP_EOL;
    echo "  " . $e->getMessage() . PHP_EOL;
} finally {
    @unlink(__DIR__ . '/env.json');
}
echo PHP_EOL;

// Demo 3: Missing required fields
echo "3. Testing missing required fields:" . PHP_EOL;
try {
    $incompleteEnv = [
        '$schema' => './env.schema.json',
        'DEBUG_MODE' => 'true'
        // Missing DATABASE_URL and API_KEY (required fields)
    ];

    file_put_contents(__DIR__ . '/env.json', json_encode($incompleteEnv, JSON_PRETTY_PRINT));

    $envJson = new EnvJson();
    $env = $envJson->load(__DIR__);

    echo "✗ Unexpected success - should have failed validation!" . PHP_EOL;

} catch (InvalidEnvJsonException $e) {
    echo "✓ Caught expected validation error for missing required fields:" . PHP_EOL;
    echo "  " . $e->getMessage() . PHP_EOL;
} finally {
    @unlink(__DIR__ . '/env.json');
}
echo PHP_EOL;

echo "Schema validation demo completed!" . PHP_EOL;
echo PHP_EOL;

echo "Common validation errors:" . PHP_EOL;
echo "- Pattern mismatch: DATABASE_URL must start with 'mysql://'" . PHP_EOL;
echo "- Length constraints: API_KEY must be at least 32 characters" . PHP_EOL;
echo "- Enum values: DEBUG_MODE must be 'true' or 'false'" . PHP_EOL;
echo "- Pattern validation: PORT must be numeric" . PHP_EOL;
echo "- Required fields: DATABASE_URL and API_KEY are mandatory" . PHP_EOL;
