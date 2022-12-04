<?php

declare(strict_types=1);

namespace Koriym\EnvJson;

use JsonSchema\Validator;
use Koriym\EnvJson\Exception\EnvJsonFileNotFoundException;
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
    /** @var Env */
    private $envFactory;

    public function __construct()
    {
        $this->envFactory = new Env();
    }

    public function load(string $dir): void
    {
        $shcema = $this->getSchema($dir);
        if ($this->isValidEnv($shcema, new Validator())) {
            return;
        }

        $json = $this->getEnvJson($dir);
        $this->putEnv($json);

        $validator = new Validator();
        if ($this->isValidEnv($shcema, $validator)) {
            return;
        }

        (new ThrowError())($validator);
    }

    private function getEnvJson(string $dir): stdClass
    {
        $envJsonFile = realpath(sprintf('%s/env.json', $dir));
        $envDistJsonFile = realpath(sprintf('%s/env.dist.json', $dir));
        if ($envJsonFile) {
            return json_decode(file_get_contents($envJsonFile), false, 512, JSON_THROW_ON_ERROR); // @phpstan-ignore-line
        }

        if ($envDistJsonFile) {
            return json_decode(file_get_contents($envDistJsonFile), false, 512, JSON_THROW_ON_ERROR); // @phpstan-ignore-line
        }

        throw new EnvJsonFileNotFoundException($dir);
    }

    private function putEnv(stdClass $json): void
    {
        foreach ($json as $key => $val) {
            if ($key[1] !== '$') {
                putenv("{$key}={$val}");
            }
        }
    }

    private function isValidEnv(stdClass $shcema, Validator $validator): bool
    {
        $env = ($this->envFactory)($shcema);
        $validator->validate($env, $shcema);

        return $validator->isValid();
    }

    /**
     * @param $dir
     *
     * @return mixed
     */
    public function getSchema($dir)
    {
        $schemaJsonFile = sprintf('%s/env.schema.json', $dir);
        if (! file_exists($schemaJsonFile)) {
            throw new SchemaFileNotFoundException($schemaJsonFile);
        }

        return json_decode(file_get_contents($schemaJsonFile)); // @phpstan-ignore-line
    }
}
