<?php

declare(strict_types=1);

namespace Koriym\EnvJson;

use Koriym\EnvJson\Exception\JsonFileNotFoundException;
use stdClass;

use function assert;
use function file_exists;
use function file_get_contents;
use function json_decode;
use function str_replace;

final class JsonLoad
{
    public function __invoke(string $json): stdClass
    {
        if (file_exists($json)) {
            return $this->loadJson($json);
        }

        $dist = str_replace('.json', '.dist.json', $json);
        if (file_exists($dist)) {
            return $this->loadJson($dist);
        }

        throw new JsonFileNotFoundException($json);
    }

    private function loadJson(string $json): stdClass
    {
        $object =  json_decode((string) file_get_contents($json));
        assert($object instanceof stdClass);

        return $object;
    }
}
