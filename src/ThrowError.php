<?php

declare(strict_types=1);

namespace Koriym\EnvJson;

use JsonSchema\Validator;
use Koriym\EnvJson\Exception\InvalidEnvJsonException;

use function sprintf;

final class ThrowError
{
    public function __invoke(Validator $validator): void
    {
        $msg = '';
        foreach ($validator->getErrors() as $error) {
            $msg .= sprintf('[%s] %s; ', $error['property'], $error['message']);
        }

        throw new InvalidEnvJsonException($msg);
    }
}
