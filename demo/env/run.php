<?php

use Koriym\EnvJson\EnvJson;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

putenv("FOO=foo2");
putenv("BAR=bar2");
(new EnvJson())->load(__DIR__);

assert(getenv('FOO') === 'foo2');
assert(getenv('BAR') === 'bar2');

echo 'It works!' . PHP_EOL;
