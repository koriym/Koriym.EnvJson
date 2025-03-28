<?php

declare(strict_types=1);

namespace Koriym\EnvJson;

use JsonSchema\Validator;
use Koriym\EnvJson\Exception\EnvJsonFileNotFoundException;
use Koriym\EnvJson\Exception\SchemaFileNotFoundException;
use stdClass;

use function dirname;
use function file_exists;
use function file_get_contents;
use function getenv;
use function is_callable;
use function json_decode;
use function putenv;
use function realpath;
use function set_error_handler;
use function sprintf;
use function str_contains;
use function str_replace;

use const E_DEPRECATED;
use const JSON_THROW_ON_ERROR;
use const PHP_VERSION_ID;

final class EnvJson
{
    /** @var Env */
    private $envFactory;

    public function __construct()
    {
        $this->envFactory = new Env();
    }

    public function load(string $dir, string $json = 'env.json'): void
    {
        $handler = $this->suppressPhp81DeprecatedError();
        $schema = $this->getSchema($dir, $json);
        if ($this->isValidEnv($schema, new Validator())) {
            goto validated;
        }

        $env = $this->getEnv($dir, $json);
        $this->putEnv($env);
        $validator = new Validator();
        if ($this->isValidEnv($schema, $validator)) {
            goto validated;
        }

        (new ThrowError())($validator);
        validated:
        if (is_callable($handler)) {
            set_error_handler($handler);
        }
    }

    private function suppressPhp81DeprecatedError(): ?callable
    {
        if (PHP_VERSION_ID >= 80100) {
            return set_error_handler(static function (int $errno, string $errstr, string $errfile) {
                unset($errstr);

                return $errno === E_DEPRECATED && str_contains($errfile, dirname(__DIR__) . '/vendor');
            });
        }

        return null;
    }

    /** @return array<string, string> */
    private function getEnv(string $dir, string $jsonName): array
    {
        $envJsonFile = realpath(sprintf('%s/%s', $dir, $jsonName));
        $envDistJsonFile = realpath(sprintf('%s/%s', $dir, str_replace('.json', '.dist.json', $jsonName)));
        if ($envJsonFile) {
            return json_decode(file_get_contents($envJsonFile), true, 512, JSON_THROW_ON_ERROR); // @phpstan-ignore-line
        }

        if ($envDistJsonFile) {
            return json_decode(file_get_contents($envDistJsonFile), true, 512, JSON_THROW_ON_ERROR); // @phpstan-ignore-line
        }

        throw new EnvJsonFileNotFoundException($dir);
    }

    /** @param array<string, string> $json */
    private function putEnv(array $json): void
    {
        foreach ($json as $key => $val) {
            if ($key[1] !== '$') {
                putenv("{$key}={$val}");
                $_ENV[$key] = $val;
            }
        }
    }

    /**
     * Collects environment variables based on schema properties
     * This replaces the Env class approach with direct getenv() calls
     */
    private function collectEnvFromSchema(stdClass $schema): stdClass
    {
        $data = new stdClass();

        // Make sure schema has properties
        if (! isset($schema->properties)) {
            return $data;
        }

        // Get each property from the environment using getenv()
        foreach ($schema->properties as $key => $property) {
            $value = getenv($key);
            if ($value !== false) {
                $data->{$key} = $value;
            }
        }

        return $data;
    }

    private function isValidEnv(stdClass $schema, Validator $validator): bool
    {
        // Use the schema-based approach instead of Env class
        $env = $this->collectEnvFromSchema($schema);

        $validator->validate($env, $schema);

        return $validator->isValid();
    }

    public function getSchema(string $dir, string $envJson): stdClass
    {
        $schemaJsonFile = sprintf('%s/%s', $dir, str_replace('.json', '.schema.json', $envJson));
        if (! file_exists($schemaJsonFile)) {
            throw new SchemaFileNotFoundException($schemaJsonFile);
        }

        return json_decode(file_get_contents($schemaJsonFile)); // @phpstan-ignore-line
    }
}
