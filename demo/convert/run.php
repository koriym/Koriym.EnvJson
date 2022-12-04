<?php

use Koriym\EnvJson\EnvJson;

@unlink(__DIR__ . '/env.json');
@unlink(__DIR__ . '/env.schema.json');

passthru(dirname(__DIR__, 2) . '/bin/ini2json ' . __DIR__ . '/.env');

require dirname(__DIR__, 2) . '/vendor/autoload.php';

(new EnvJson(__DIR__))->load();

assert(getenv('FOO') === 'foo1');
assert(getenv('BAR') === 'bar1');
assert(getenv('API') === 'http://example.com');

echo PHP_EOL;
echo 'It works!' . PHP_EOL;
