<?php

declare(strict_types=1);

namespace Koriym\EnvJson;

use JsonSchema\Validator;
use Koriym\EnvJson\Exception\InvalidEnvJsonException;
use Koriym\EnvJson\Exception\InvalidJsonSchemaException;
use Koriym\EnvJson\Exception\SchemaFileNotFoundException;
use Koriym\EnvJson\Exception\SchemaFileNotReadableException;
use stdClass;

use function assert;
use function dirname;
use function file_exists;
use function file_get_contents;
use function get_object_vars;
use function getenv;
use function is_array;
use function is_object;
use function is_scalar;
use function json_decode;
use function putenv;
use function realpath;
use function set_error_handler;
use function sprintf;
use function str_contains;
use function str_replace;

use const E_DEPRECATED;
use const JSON_THROW_ON_ERROR;

final class EnvJson
{
    private Validator $validator;

    public function __construct()
    {
        $this->validator = new Validator();
    }

    public function load(string $dir, string $json = 'env.json'): stdClass
    {
        $handler = $this->suppressPhp81DeprecatedError();
        $schema = $this->getSchema($dir, $json);

        $pureEnv = $this->collectEnvFromSchema($schema);
        $this->validator->validate($pureEnv, $schema);
        $isEnvValid = $this->validator->isValid();

        if ($isEnvValid) {
            set_error_handler($handler);
            assert($pureEnv instanceof stdClass); // Add assertion

            return $pureEnv;
        }

        $fileEnv = $this->getEnv($dir, $json);
        $this->putEnv($fileEnv);
        $pureEnvByFile = $this->collectEnvFromSchema($schema);
        $this->validator->validate($pureEnvByFile, $schema);

        $isPureEnvByFileValid = $this->validator->isValid();

        if ($isPureEnvByFileValid) {
            set_error_handler($handler);
            assert($pureEnvByFile instanceof stdClass);

            return $pureEnvByFile;
        }

        // If fileEnv was empty (no file found) and existing env was not valid, return empty object
        if (empty($fileEnv)) {
            set_error_handler($handler);

            return new stdClass();
        }

        // Otherwise, if file existed but was invalid according to schema, throw exception
        throw new InvalidEnvJsonException($this->validator);
    }

    private function suppressPhp81DeprecatedError(): ?callable
    {
        return set_error_handler(static function (int $errno, string $errstr, string $errfile) {
            unset($errstr);

            return $errno === E_DEPRECATED && str_contains($errfile, dirname(__DIR__) . '/vendor');
        });
    }

    /** @return array<string, mixed> */
    private function getEnv(string $dir, string $jsonName): array
    {
        $envJsonFile = realpath(sprintf('%s/%s', $dir, $jsonName));
        $envDistJsonFile = realpath(sprintf('%s/%s', $dir, str_replace('.json', '.dist.json', $jsonName)));

        if ($envJsonFile !== false) {
            $contents = file_get_contents($envJsonFile);
            if ($contents === false) {
                throw new Exception\RuntimeException("Failed to read env file: {$envJsonFile}");
            }

            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($decoded)) {
                throw new Exception\RuntimeException("Invalid JSON format in env file: {$envJsonFile}. Expected array.");
            }

            // Although we expect array<string, string>, PHPStan might still complain.
            // We rely on schema validation later to enforce types.
            /** @var array<string, mixed> $decoded */ // Acknowledge values can be mixed initially
            return $decoded; // Return the array
        }

        if ($envDistJsonFile !== false) {
            $contents = file_get_contents($envDistJsonFile);
            if ($contents === false) {
                throw new Exception\RuntimeException("Failed to read env file: {$envDistJsonFile}");
            }

            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($decoded)) {
                throw new Exception\RuntimeException("Invalid JSON format in env file: {$envDistJsonFile}. Expected array.");
            }

            // Although we expect array<string, string>, PHPStan might still complain.
            // We rely on schema validation later to enforce types.
             /** @var array<string, mixed> $decoded */ // Acknowledge values can be mixed initially
            return $decoded; // Return the array
        }

        // If neither file exists, return empty array
        return [];
    }

    /** @param array<string, mixed> $json */
    private function putEnv(array $json): void
    {
        foreach ($json as $key => $val) {
            // Ensure value is scalar before putting env (key is guaranteed string)
            if ($key[1] !== '$' && is_scalar($val)) {
                $stringValue = (string) $val;
                putenv("{$key}={$stringValue}");
                $_ENV[$key] = $stringValue;
            }
        }
    }

    /**
     * Collects environment variables based on schema properties.
     * This replaces the Env class approach with direct getenv() calls.
     */
    private function collectEnvFromSchema(stdClass $schema): stdClass
    {
        $data = new stdClass();

        // If schema has no properties defined, or properties is not an object, return empty object
        if (! isset($schema->properties) || ! is_object($schema->properties)) {
            return $data;
        }

        // Get object properties as an associative array
        $properties = get_object_vars($schema->properties);

        // Get each property from the environment using getenv()
        /** @var mixed $property */ // Keep $property for potential future use if needed, but mark as mixed
        foreach ($properties as $key => $property) {
            unset($property); // Explicitly unset if not used
            // Removed unnecessary @var string $key
            $value = getenv($key);
            if ($value !== false) {
                // Dynamically set property on stdClass
                $data->{$key} = $value;
            }
        }

        return $data; // Always returns stdClass
    }

    public function getSchema(string $dir, string $envJson): stdClass
    {
        // Always look for 'env.schema.json', regardless of the $envJson filename
        unset($envJson); // Indicate $envJson is not used for schema path determination
        $schemaJsonFile = sprintf('%s/env.schema.json', $dir);
        if (! file_exists($schemaJsonFile)) {
            throw new SchemaFileNotFoundException($schemaJsonFile);
        }

        $schemaContents = file_get_contents($schemaJsonFile);
        if ($schemaContents === false) {
            throw new SchemaFileNotReadableException($schemaJsonFile);
        }

        $decodedSchema = json_decode($schemaContents);

        // Ensure the decoded schema is an object (stdClass)
        if (! $decodedSchema instanceof stdClass) {
            throw new InvalidJsonSchemaException($schemaJsonFile);
        }

        return $decodedSchema;
    }
}
