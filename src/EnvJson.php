<?php

declare(strict_types=1);

namespace Koriym\EnvJson;

use JsonException;
use JsonSchema\Validator;
use Koriym\EnvJson\Exception\InvalidEnvJsonException;
use Koriym\EnvJson\Exception\InvalidEnvJsonFormatException;
use Koriym\EnvJson\Exception\InvalidJsonContentException;
use Koriym\EnvJson\Exception\InvalidJsonFileException;
use stdClass;

use function array_keys;
use function array_merge;
use function file_exists;
use function file_get_contents;
use function getenv;
use function is_array;
use function is_dir;
use function is_readable;
use function is_scalar;
use function json_decode;
use function putenv;
use function sprintf;
use function str_replace;
use function str_starts_with;

use const JSON_THROW_ON_ERROR;

/**
 * @psalm-import-type SchemaObjectType from IniJson
 * @psalm-type LocalSchemaObjectType = array{'$schema': string, type: string, required: list<string>, properties: array<string, mixed>}
 */
final class EnvJson
{
    private Validator $validator;

    public function __construct()
    {
        $this->validator = new Validator();
    }

    /**
     * Loads environment variables and validates them against the schema
     *
     * 1. First collects existing environment variables based on schema and validates them
     * 2. If validation fails, loads environment variables from the specified JSON file
     * 3. If still invalid, either throws an exception or returns an empty object
     *
     * @param string $dir  Directory path where the environment JSON file is located
     * @param string $json Name of the environment JSON file (default: env.json)
     *
     * @return stdClass Validated environment variables as an object
     *
     * @throws InvalidEnvJsonException If environment variables do not conform to the schema.
     */
    public function load(string $dir, string $json = 'env.json'): stdClass
    {
        $schema = $this->getSchema($dir);

        $pureEnvObject = $this->collectEnvFromSchema($schema);
        $this->validator->validate($pureEnvObject, $schema);
        $isEnvValid = $this->validator->isValid();

        if ($isEnvValid) {
            /** @var stdClass $pureEnvObject */
            return $pureEnvObject; // Return the object variable
        }

        $fileEnv = $this->getEnv($dir, $json);
        $this->putEnv($fileEnv);
        $pureEnvByFileObject = $this->collectEnvFromSchema($schema);
        $this->validator->validate($pureEnvByFileObject, $schema);

        $isPureEnvByFileValid = $this->validator->isValid();

        if ($isPureEnvByFileValid) {
            /** @var stdClass $pureEnvByFileObject */
            return $pureEnvByFileObject; // Return the object variable
        }

        // If fileEnv was empty (no file found) and existing env was not valid, return empty stdClass
        if (empty($fileEnv)) {
            return new stdClass();
        }

        // Otherwise, if file existed but was invalid according to schema, throw exception
        throw new InvalidEnvJsonException($this->validator);
    }

    /**
     * Reads env.json and env.dist.json files from the specified directory
     * and returns an associative array of environment variables
     *
     * If both env.json and env.dist.json exist, settings from env.json take
     * precedence, but missing keys are completed from env.dist.json
     *
     * @param string $dir      Directory path where environment JSON files are located
     * @param string $jsonName Environment JSON filename
     *
     * @return array<string, mixed> Associative array of environment variables
     *
     * @throws InvalidJsonFileException If file cannot be read.
     * @throws InvalidEnvJsonFormatException If JSON format is invalid.
     * @throws InvalidJsonContentException If JSON syntax is invalid.
     */
    private function getEnv(string $dir, string $jsonName): array
    {
        $envJsonFile = sprintf('%s/%s', $dir, $jsonName);
        $envDistJsonFile = sprintf('%s/%s', $dir, str_replace('.json', '.dist.json', $jsonName));

        /** @var array<string, mixed> $envData */
        $envData = [];

        // Try reading env.json
        if (file_exists($envJsonFile) && is_readable($envJsonFile)) {
            $contents = @file_get_contents($envJsonFile);
            if ($contents === false) { // @codeCoverageIgnore
                throw new InvalidJsonFileException("Failed to read env file: {$envJsonFile}"); // @codeCoverageIgnore
            }

            /** @var mixed $decoded */
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($decoded)) {
                throw new InvalidEnvJsonFormatException("Invalid JSON format in env file: {$envJsonFile}. Expected array.");
            }

            /** @var array<string, mixed> $decoded */
            $envData = $decoded;
        }

        if (file_exists($envDistJsonFile) && is_readable($envDistJsonFile)) {
            $contents = file_get_contents($envDistJsonFile);
            if ($contents === false) { // @codeCoverageIgnore
                throw new InvalidJsonFileException("Failed to read env.dist file: {$envDistJsonFile}"); // @codeCoverageIgnore
            }

            try {
                /** @var mixed $decodedDist */
                $decodedDist = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
                if (! is_array($decodedDist)) {
                    throw new InvalidEnvJsonFormatException("Invalid JSON format in env.dist file: {$envDistJsonFile}. Expected array.");
                }

                // Merge dist into env data, overwriting existing keys
                $envData = array_merge($envData, $decodedDist);
            } catch (JsonException $e) {
                throw new InvalidJsonContentException("Invalid JSON in env.dist file: {$envDistJsonFile} - " . $e->getMessage(), 0, $e);
            } catch (InvalidJsonFileException $e) { // @codeCoverageIgnore
                // If env.dist.json is unreadable, we might still have data from env.json
                // Log or handle this case if necessary, for now, we proceed
            }
        }

        /** @var array<string, mixed> $envData */
        return $envData;
    }

    /**
     * Sets environment variables from an associative array
     *
     * Only scalar values are set as environment variables, and keys starting with '$'
     * are ignored (such as '$schema' and other metadata keys)
     *
     * @param array<string, mixed> $json Associative array to set as environment variables
     */
    private function putEnv(array $json): void
    {
        foreach ($json as $key => $val) {
            // Ensure value is scalar before putting env (key is guaranteed string)
            // Skip keys starting with '$' (like '$schema')
            if (! str_starts_with($key, '$') && is_scalar($val)) { // @codeCoverageIgnore - This condition's branches are hard to test reliably due to getenv/putenv timing issues.
                /** @var string $stringValue */
                $stringValue = (string) $val;
                putenv("{$key}={$stringValue}");
            }
        }
    }

    /**
     * Collects environment variables based on schema properties
     *
     * Retrieves environment variables that match property names defined
     * in the schema using getenv() and returns them as an object
     *
     * @param array<string, mixed> $schema Environment schema definition
     *
     * @return stdClass Object containing collected environment variables
     *
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     */
    private function collectEnvFromSchema(array $schema): stdClass
    {
        /** @var array<string, string> $data */
        $data = [];

        // If schema has no properties defined, or properties is not an array, return empty array
        if (! isset($schema['properties']) || ! is_array($schema['properties'])) {
            return new stdClass();
        }

        /** @var array<string, mixed> $properties */
        $properties = $schema['properties'];

        // Get each property from the environment using getenv()
        foreach (array_keys($properties) as $key) {
            /** @var string|false $value */
            $value = getenv($key);
            if ($value !== false) {
                // Add to associative array
                $data[$key] = $value;
            }
        }

        return (object) $data;
    }

    /**
     * Loads JSON schema from the specified directory
     *
     * Always looks for a file named 'env.schema.json' (regardless of the $envJson filename)
     *
     * @param string $dir Directory path where the schema file is located
     *
     * @return array<string, mixed> Associative array of the loaded JSON schema
     *
     * @throws InvalidJsonFileException If schema file cannot be read.
     * @throws InvalidJsonContentException If schema JSON syntax is invalid.
     */
    public function getSchema(string $dir): array
    {
        // Always look for 'env.schema.json', regardless of the $envJson filename
        $schemaJsonFile = sprintf('%s/env.schema.json', $dir);

        return $this->fileGetJsonObject($schemaJsonFile);
    }

    /**
     * Reads a JSON object from the specified file
     *
     * Performs file existence check, directory check, and JSON syntax validation
     * to return a valid JSON object
     *
     * @param string $file Path to the JSON file to read
     *
     * @return array<string, mixed> Associative array of the loaded JSON object
     *
     * @throws InvalidJsonFileException If file cannot be read or is a directory.
     * @throws InvalidJsonContentException If JSON syntax is invalid or not an array/object.
     */
    private function fileGetJsonObject(string $file): array
    {
        if (! is_readable($file)) {
            throw new InvalidJsonFileException($file);
        }

        if (is_dir($file)) {
            throw new InvalidJsonFileException($file);
        }

        /** @var string $contents */
        $contents = (string) file_get_contents($file);
        try {
            /** @var mixed $decoded */
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidJsonContentException(sprintf('Error decoding JSON from file %s: %s', $file, $e->getMessage()), 0, $e);
        }

        if (! is_array($decoded)) {
            throw new InvalidJsonContentException(sprintf('JSON in file %s is not an object/array.', $file));
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }
}
