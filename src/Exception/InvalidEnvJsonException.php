<?php

declare(strict_types=1);

namespace Koriym\EnvJson\Exception;

use JsonSchema\Validator;
use RuntimeException;

use function sprintf;

class InvalidEnvJsonException extends RuntimeException
{
    public function __construct(Validator $validator)
    {
        $msg = '';
        foreach ($validator->getErrors() as $error) {
            $msg .= sprintf('[%s] %s; ', $error['property'], $error['message']);
        }

        parent::__construct($msg);
    }
}
