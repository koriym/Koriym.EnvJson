<?php

use Koriym\EnvJson\EnvJson;
use Koriym\EnvJson\IniJson;
use Koriym\EnvJson\Exception\InvalidIniFileException;
use Koriym\EnvJson\Exception\InvalidEnvJsonException;
use Koriym\EnvJson\Exception\InvalidJsonFileException;
use Koriym\EnvJson\Exception\InvalidJsonContentException;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

echo "=== Error Handling Demo ===" . PHP_EOL;
echo PHP_EOL;

// Demo 1: Invalid INI file - create a truly invalid INI file
echo "1. Testing invalid INI file:" . PHP_EOL;
try {
    // Create a truly invalid INI file
    file_put_contents(__DIR__ . '/truly-invalid.ini', "FOO=bar\n[invalid section without closing\nBAR=baz");

    $iniJson = new IniJson(__DIR__ . '/truly-invalid.ini');
    echo "Unexpected success - should have failed!" . PHP_EOL;
} catch (InvalidIniFileException $e) {
    echo "✓ Caught expected exception: " . $e->getMessage() . PHP_EOL;
} finally {
    @unlink(__DIR__ . '/truly-invalid.ini');
}
echo PHP_EOL;

// Demo 2: Non-existent file
echo "2. Testing non-existent file:" . PHP_EOL;
try {
    $iniJson = new IniJson(__DIR__ . '/non-existent.ini');
    echo "Unexpected success - should have failed!" . PHP_EOL;
} catch (InvalidIniFileException $e) {
    echo "✓ Caught expected exception: " . $e->getMessage() . PHP_EOL;
}
echo PHP_EOL;

// Demo 3: Invalid JSON file
echo "3. Testing invalid env.json file:" . PHP_EOL;
try {
    // Create an invalid JSON file
    file_put_contents(__DIR__ . '/env.json', '{ "invalid": json }');

    $envJson = new EnvJson();
    $envJson->load(__DIR__);
    echo "Unexpected success - should have failed!" . PHP_EOL;
} catch (InvalidJsonContentException $e) {
    echo "✓ Caught expected JSON content exception: " . $e->getMessage() . PHP_EOL;
} catch (InvalidJsonFileException $e) {
    echo "✓ Caught expected JSON file exception: " . $e->getMessage() . PHP_EOL;
} catch (InvalidEnvJsonException $e) {
    echo "✓ Caught expected validation exception: " . $e->getMessage() . PHP_EOL;
} finally {
    @unlink(__DIR__ . '/env.json');
}
echo PHP_EOL;

// Demo 4: Invalid schema file
echo "4. Testing invalid schema file:" . PHP_EOL;
try {
    // Create valid env.json but invalid schema
    file_put_contents(__DIR__ . '/env.json', '{"$schema": "./env.schema.json", "FOO": "bar"}');
    file_put_contents(__DIR__ . '/env.schema.json', '{ "invalid": schema }');

    $envJson = new EnvJson();
    $envJson->load(__DIR__);
    echo "Unexpected success - should have failed!" . PHP_EOL;
} catch (InvalidJsonContentException $e) {
    echo "✓ Caught expected JSON content exception: " . $e->getMessage() . PHP_EOL;
} catch (InvalidJsonFileException $e) {
    echo "✓ Caught expected schema file exception: " . $e->getMessage() . PHP_EOL;
} finally {
    @unlink(__DIR__ . '/env.json');
    @unlink(__DIR__ . '/env.schema.json');
}
echo PHP_EOL;

echo "Error handling demo completed!" . PHP_EOL;
echo PHP_EOL;
echo "This demo shows how the library handles various error conditions:" . PHP_EOL;
echo "- Invalid INI file syntax" . PHP_EOL;
echo "- Non-existent files" . PHP_EOL;
echo "- Invalid JSON syntax in env.json" . PHP_EOL;
echo "- Invalid JSON syntax in schema files" . PHP_EOL;
