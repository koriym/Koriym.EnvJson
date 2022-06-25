<?php

declare(strict_types=1);

namespace Koriym\EnvJson;

use JsonSchema\Validator;

final class Validate
{
    public function __invoke(object $data, object $schema): void
    {
        $validator = new Validator();
        $validator->validate($data, $schema);

        if ($validator->isValid()) {
            return;
        }

        (new ThrowError())($validator);
    }
}
