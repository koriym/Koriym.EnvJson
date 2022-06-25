<?php

declare(strict_types=1);

namespace Koriym\EnvJson;

use stdClass;

use function getenv;

final class EnvLoad
{
    public function __invoke(): object
    {
        $data = new stdClass();
        $env = getenv();
        foreach ($env as $key => $val) {
            $data->{$key} = $val;
        }

        return $data;
    }
}
