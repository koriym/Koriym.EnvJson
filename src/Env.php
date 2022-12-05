<?php

declare(strict_types=1);

namespace Koriym\EnvJson;

use stdClass;

use function array_keys;
use function assert;
use function getenv;
use function in_array;

final class Env
{
    public function __invoke(object $schema): stdClass
    {
        assert(isset($schema->properties));
        $schemaKeys = array_keys((array) $schema->properties);
        $data = new stdClass();
        $env = getenv();
        foreach ($env as $key => $val) {
            if (in_array($key, $schemaKeys)) {
                $data->{$key} = $val;
            }
        }

        return $data;
    }
}
