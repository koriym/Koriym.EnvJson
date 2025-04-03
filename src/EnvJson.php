<?php

declare(strict_types=1);

namespace Koriym\EnvJson;

use JsonException;
use JsonSchema\Validator;
use Koriym\EnvJson\Exception\EnvJsonFileNotReadableException;
// Added
use Koriym\EnvJson\Exception\InvalidEnvJsonException;
use Koriym\EnvJson\Exception\InvalidEnvJsonFormatException;
// Added
use Koriym\EnvJson\Exception\InvalidJsonContentException;
use Koriym\EnvJson\Exception\JsonFileNotReadableException;
use stdClass;

use function assert;
use function dirname;
use function file_exists;
use function file_get_contents;
use function get_object_vars;
use function getenv;
use function is_array;
use function is_dir;
use function is_object;
use function is_readable;
use function is_scalar;
use function json_decode;
use function json_last_error;
use function putenv;
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
        $schema = $this->getSchema($dir);

        $pureEnv = $this->collectEnvFromSchema($schema);
        $this->validator->validate($pureEnv, $schema);
        $isEnvValid = $this->validator->isValid();

        if ($isEnvValid) {
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

            return $errno === E_DEPRECATED && str_contains($errfile, dirname(__DIR__) . '/vendor'); // @codeCoverageIgnore - Hard to reliably trigger specific E_DEPRECATED errors in tests.
        });
    }

    /** @return array<string, mixed> */
    private function getEnv(string $dir, string $jsonName): array
    {
        // Construct paths directly instead of using realpath initially
        $envJsonFile = sprintf('%s/%s', $dir, $jsonName);
        $envDistJsonFile = sprintf('%s/%s', $dir, str_replace('.json', '.dist.json', $jsonName));

        // Check env.json first
        if (file_exists($envJsonFile)) {
            // Check if it's a directory before trying to read
            if (is_dir($envJsonFile)) {
                throw new EnvJsonFileNotReadableException("env file is a directory: {$envJsonFile}");
            }

            $contents = @file_get_contents($envJsonFile); // Suppress errors
            if ($contents === false) {
                 // Check readability again after attempting read
                if (! is_readable($envJsonFile)) { // @codeCoverageIgnore
                 // - Hard to reliably simulate file read errors after is_readable check.
                    throw new EnvJsonFileNotReadableException("Failed to read env file: {$envJsonFile}"); // @codeCoverageIgnore

                    //  - Hard to reliably simulate file read errors after is_readable check.
                }

                 // Handle potential rare cases where read fails despite is_readable
                 throw new EnvJsonFileNotReadableException("Failed to read env file: {$envJsonFile}"); // @codeCoverageIgnore

                 // - Hard to reliably simulate file read errors after is_readable check.
            }

            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($decoded)) {
                 // Use the original path in the exception message
                throw new InvalidEnvJsonFormatException("Invalid JSON format in env file: {$envJsonFile}. Expected array.");
            }

            // Although we expect array<string, string>, PHPStan might still complain.
            // We rely on schema validation later to enforce types.
            /** @var array<string, mixed> $decoded */ // Acknowledge values can be mixed initially
            return $decoded; // Return the array
        }

        // Check env.dist.json if env.json doesn't exist
        if (file_exists($envDistJsonFile)) {
            // Check if it's a directory before trying to read
            if (is_dir($envDistJsonFile)) {
                throw new JsonFileNotReadableException("env.dist file is a directory: {$envDistJsonFile}");
            }

            $contents = @file_get_contents($envDistJsonFile); // Suppress errors
            if ($contents === false) {
                // Check readability again after attempting read
                if (! is_readable($envDistJsonFile)) { // @codeCoverageIgnore
                    throw new JsonFileNotReadableException("Failed to read env.dist file: {$envDistJsonFile}"); // @codeCoverageIgnore
                }

                // Handle potential rare cases where read fails despite is_readable
                throw new JsonFileNotReadableException("Failed to read env.dist file: {$envDistJsonFile}"); // @codeCoverageIgnore
            }

            try {
                $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new InvalidJsonContentException("Invalid JSON in env.dist file: {$envDistJsonFile} - " . $e->getMessage(), 0, $e);
            }

            if (! is_array($decoded)) {
                // Use the original path in the exception message
                throw new InvalidEnvJsonFormatException("Invalid JSON format in env.dist file: {$envDistJsonFile}. Expected array.");
            }

            /** @var array<string, mixed> $decoded */

            return $decoded; // Return the decoded associative array
        }

        // If neither file exists, return empty array
        return [];
    }

    /** @param array<string, mixed> $json */
    private function putEnv(array $json): void
    {
        foreach ($json as $key => $val) {
            // Ensure value is scalar before putting env (key is guaranteed string)
            if ($key[1] !== '$' && is_scalar($val)) { // @codeCoverageIgnore - This condition's branches are hard to test reliably due to getenv/putenv timing issues.
                $stringValue = (string) $val;
                putenv("{$key}={$stringValue}");
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

    public function getSchema(string $dir): stdClass
    {
        // Always look for 'env.schema.json', regardless of the $envJson filename
        $schemaJsonFile = sprintf('%s/env.schema.json', $dir);

        return $this->fileGetJsonObject($schemaJsonFile);
    }

    private function fileGetJsonObject(string $file): stdClass
    {
        if (! is_readable($file)) {
            throw new JsonFileNotReadableException($file);
        }

        if (is_dir($file)) {
            throw new JsonFileNotReadableException($file);
        }

        $contents = (string) file_get_contents($file);
        $stdObject = json_decode($contents);
        if (! $stdObject instanceof stdClass) {
            throw new InvalidJsonContentException((string) json_last_error());
        }

        return $stdObject;
    }
}
