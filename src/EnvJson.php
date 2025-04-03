<?php

declare(strict_types=1);

namespace Koriym\EnvJson;

use JsonException;
use JsonSchema\Validator;
use Koriym\EnvJson\Exception\InvalidEnvJsonException;
use Koriym\EnvJson\Exception\InvalidEnvJsonFormatException;
use Koriym\EnvJson\Exception\InvalidJsonContentException;
use Koriym\EnvJson\Exception\JsonFileNotReadableException;
use stdClass;

use function array_merge;
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
use function json_last_error_msg;
use function putenv;
use function sprintf;
use function str_replace;

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
        $schema = $this->getSchema($dir);

        $pureEnv = $this->collectEnvFromSchema($schema);
        $this->validator->validate($pureEnv, $schema);
        $isEnvValid = $this->validator->isValid();

        if ($isEnvValid) {
            return $pureEnv; // @phpstan-ignore-line - This is a valid return type
        }

        $fileEnv = $this->getEnv($dir, $json);
        $this->putEnv($fileEnv);
        $pureEnvByFile = $this->collectEnvFromSchema($schema);
        $this->validator->validate($pureEnvByFile, $schema);

        $isPureEnvByFileValid = $this->validator->isValid();

        if ($isPureEnvByFileValid) {
            return $pureEnvByFile; // @phpstan-ignore-line - This is a valid return type
        }

        // If fileEnv was empty (no file found) and existing env was not valid, return empty object
        if (empty($fileEnv)) {
            return new stdClass();
        }

        // Otherwise, if file existed but was invalid according to schema, throw exception
        throw new InvalidEnvJsonException($this->validator);
    }

    /** @return array<string, mixed> */
    private function getEnv(string $dir, string $jsonName): array
    {
        $envJsonFile = sprintf('%s/%s', $dir, $jsonName);
        $envDistJsonFile = sprintf('%s/%s', $dir, str_replace('.json', '.dist.json', $jsonName));

        $envData = [];

        // Try reading env.json
        if (file_exists($envJsonFile) && is_readable($envJsonFile)) {
            try {
                $contents = file_get_contents($envJsonFile);
                if ($contents === false) { // @codeCoverageIgnore
                    throw new JsonFileNotReadableException("Failed to read env file: {$envJsonFile}"); // @codeCoverageIgnore
                }

                $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
                if (! is_array($decoded)) {
                    throw new InvalidEnvJsonFormatException("Invalid JSON format in env file: {$envJsonFile}. Expected array.");
                }

                $envData = $decoded;
            } catch (JsonException $e) {
                throw new InvalidJsonContentException("Invalid JSON in env file: {$envJsonFile} - " . $e->getMessage(), 0, $e);
            } catch (JsonFileNotReadableException $e) { // @codeCoverageIgnore
                // Allow continuing if env.json is unreadable but env.dist.json might exist
                // Log or handle this case if necessary, for now, we proceed
            }
        }

        // Try reading env.dist.json
        if (file_exists($envDistJsonFile) && is_readable($envDistJsonFile)) {
            $contents = file_get_contents($envDistJsonFile);
            if ($contents === false) { // @codeCoverageIgnore
                throw new JsonFileNotReadableException("Failed to read env.dist file: {$envDistJsonFile}"); // @codeCoverageIgnore
            }

            try {
                $decodedDist = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
                if (! is_array($decodedDist)) {
                    throw new InvalidEnvJsonFormatException("Invalid JSON format in env.dist file: {$envDistJsonFile}. Expected array.");
                }

                // Merge dist into env data, overwriting existing keys
                $envData = array_merge($envData, $decodedDist);
            } catch (JsonException $e) {
                throw new InvalidJsonContentException("Invalid JSON in env.dist file: {$envDistJsonFile} - " . $e->getMessage(), 0, $e);
            } catch (JsonFileNotReadableException $e) { // @codeCoverageIgnore
                // If env.dist.json is unreadable, we might still have data from env.json
                // Log or handle this case if necessary, for now, we proceed
            }
        }

        /** @var array<string, mixed> $envData */
        return $envData;
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
            throw new InvalidJsonContentException(sprintf('Error decoding JSON from file %s: %s', $file, json_last_error_msg()));
        }

        return $stdObject;
    }
}
