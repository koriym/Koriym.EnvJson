<?php

echo "=== bin/envjson CLI Tool Demo ===" . PHP_EOL;
echo PHP_EOL;

$binPath = dirname(__DIR__, 2) . '/bin/envjson';

echo "This demo shows how to use the bin/envjson command line tool." . PHP_EOL;
echo "Current directory: " . __DIR__ . PHP_EOL;
echo PHP_EOL;

echo "1. Default shell format (export statements):" . PHP_EOL;
echo "Command: {$binPath}" . PHP_EOL;
passthru($binPath . ' -d ' . __DIR__);
echo PHP_EOL;

echo "2. PHP-FPM format:" . PHP_EOL;
echo "Command: {$binPath} --output=fpm" . PHP_EOL;
passthru($binPath . ' -d ' . __DIR__ . ' --output=fpm');
echo PHP_EOL;

echo "3. INI format:" . PHP_EOL;
echo "Command: {$binPath} --output=ini" . PHP_EOL;
passthru($binPath . ' -d ' . __DIR__ . ' --output=ini');
echo PHP_EOL;

echo "4. Help output:" . PHP_EOL;
echo "Command: {$binPath} --help" . PHP_EOL;
passthru($binPath . ' --help');
echo PHP_EOL;

echo "Usage examples:" . PHP_EOL;
echo "  # Load variables into current shell:" . PHP_EOL;
echo "  source <({$binPath})" . PHP_EOL;
echo PHP_EOL;
echo "  # Generate config files for different environments:" . PHP_EOL;
echo "  {$binPath} --output=fpm > .env.fpm" . PHP_EOL;
echo "  {$binPath} --output=ini > config.ini" . PHP_EOL;
echo PHP_EOL;

echo "Demo completed!" . PHP_EOL;
