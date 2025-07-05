<?php

use Koriym\EnvJson\IniJson;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

echo "=== IniJson Demo - Direct usage of IniJson class ===" . PHP_EOL;
echo PHP_EOL;

// Create IniJson instance from sample.ini
$iniJson = new IniJson(__DIR__ . '/sample.ini');

echo "Generated JSON data:" . PHP_EOL;
echo $iniJson->data;
echo PHP_EOL;

echo "Generated JSON schema:" . PHP_EOL;
echo $iniJson->schema;
echo PHP_EOL;

// Write generated files
file_put_contents(__DIR__ . '/env.json', $iniJson->data);
file_put_contents(__DIR__ . '/env.schema.json', $iniJson->schema);

echo "Files written to:" . PHP_EOL;
echo "- env.json" . PHP_EOL;
echo "- env.schema.json" . PHP_EOL;
echo PHP_EOL;

echo "Demo completed successfully!" . PHP_EOL;
