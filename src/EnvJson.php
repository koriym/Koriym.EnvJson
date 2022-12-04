<?php

declare(strict_types=1);

namespace Koriym\EnvJson;

use JsonSchema\Validator;
use Koriym\EnvJson\Exception\SchemaFileNotFoundException;
use stdClass;

use function file_exists;
use function file_get_contents;
use function json_decode;
use function putenv;
use function realpath;
use function sprintf;

use const JSON_THROW_ON_ERROR;

final class EnvJson
{
    /** @var stdClass */
    private $shcema;

    /** @var EnvLoad */
    private $envLoad;

    /** @var array<string, string> */
    private $envJson = [];

    public function __construct(string $dir)
    {
        $this->envLoad = new EnvLoad();
        $schemaJsonFile = sprintf('%s/env.schema.json', $dir);
        if (! file_exists($schemaJsonFile)) {
            throw new SchemaFileNotFoundException($schemaJsonFile);
        }

        $this->shcema = json_decode(file_get_contents($schemaJsonFile)); // @phpstan-ignore-line
        $envJsonFile = realpath(sprintf('%s/env.json', $dir));
        $envDistJsonFile = realpath(sprintf('%s/env.dist.json', $dir));
        if ($envJsonFile) {
            $this->envJson = json_decode(file_get_contents($envJsonFile), true, 512, JSON_THROW_ON_ERROR); // @phpstan-ignore-line

            return;
        }

        if ($envDistJsonFile) {
            $this->envJson = json_decode(file_get_contents($envDistJsonFile), true, 512, JSON_THROW_ON_ERROR); // @phpstan-ignore-line
        }
    }

    public function load(): void
    {
        if ($this->isValidEnv(new Validator())) {
            return;
        }

        $this->json2env();
        $validator = new Validator();
        if ($this->isValidEnv($validator)) {
            return;
        }

        (new ThrowError())($validator);
    }

    private function isValidEnv(Validator $validator): bool
    {
        $json = ($this->envLoad)($this->shcema);
        $validator->validate($json, $this->shcema);

        return $validator->isValid();
    }

    private function json2env(): void
    {
        foreach ($this->envJson as $key => $val) {
            if ($key[0] !== '$') {
                putenv("{$key}={$val}");
            }
        }
    }
}
