<?php

declare(strict_types=1);

namespace Koriym\EnvJson\Exception;

use JsonSchema\Validator;
use RuntimeException;

use function sprintf;

final class InvalidEnvJsonException extends RuntimeException
{
    public function __construct(Validator $validator)
    {
        $msg = '';
        /** @var array{property: string, message: string} $error */
        foreach ($validator->getErrors() as $error) {
            // The PHPDoc guarantees 'property' and 'message' exist and are strings
            $msg .= sprintf('[%s] %s; ', $error['property'], $error['message']);
        }

        parent::__construct($msg);
    }
}
