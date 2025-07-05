<?php

echo "=== Running All EnvJson Demos ===" . PHP_EOL;
echo PHP_EOL;

echo "1. Convert Demo - INI to JSON conversion using binary tool" . PHP_EOL;
passthru('php ' . __DIR__ . '/convert/run.php');
echo PHP_EOL;

echo "2. Basic EnvJson Usage Demo" . PHP_EOL;
passthru('php ' . __DIR__ . '/env-json-1/run.php');
echo PHP_EOL;

echo "3. Advanced EnvJson Usage Demo" . PHP_EOL;
passthru('php ' . __DIR__ . '/env-json-2/run.php');
echo PHP_EOL;

echo "4. Environment Variable Priority Demo" . PHP_EOL;
passthru('php ' . __DIR__ . '/env/run.php');
echo PHP_EOL;

echo "5. IniJson Class Direct Usage Demo" . PHP_EOL;
passthru('php ' . __DIR__ . '/ini-json/run.php');
echo PHP_EOL;

echo "6. Error Handling Demo" . PHP_EOL;
passthru('php ' . __DIR__ . '/error-handling/run.php');
echo PHP_EOL;

echo "7. Schema Validation Demo" . PHP_EOL;
passthru('php ' . __DIR__ . '/validation/run.php');
echo PHP_EOL;

echo "=== All demos completed! ===" . PHP_EOL;
