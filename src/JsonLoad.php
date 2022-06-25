<?php

declare(strict_types=1);

namespace Koriym\EnvJson;

use function assert;
use function file_get_contents;
use function is_object;
use function json_decode;

final class JsonLoad
{
    public function __invoke(string $json): object
    {
        $object =  json_decode((string) file_get_contents($json));
        assert(is_object($object));

        return $object;
    }
}
