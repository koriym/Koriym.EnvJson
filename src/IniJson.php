<?php

declare(strict_types=1);

namespace Koriym\EnvJson;

use JsonException;
use Koriym\EnvJson\Exception\InvalidIniFileException;
use stdClass;

use function array_keys;
use function file_exists;
use function json_encode;
use function parse_ini_file;
use function sort;

use const INI_SCANNER_TYPED;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const PHP_EOL;

/**
 * @psalm-type SchemaObjectType = array{'$schema': string, type: string, required: list<string>, properties: stdClass}
 * @psalm-type DataSchemaType = array{'$schema': string, ...}
 */
final class IniJson
{
    public string $data;
    public string $schema;

    /**
     * @throws InvalidIniFileException
     * @throws JsonException
     */
    public function __construct(string $iniFile)
    {
        if (! file_exists($iniFile)) {
            throw new InvalidIniFileException("Failed to parse INI file: {$iniFile}");
        }

        /** @var array<string, mixed>|false $ini */
        $ini = @parse_ini_file($iniFile, false, INI_SCANNER_TYPED);
        if ($ini === false) {
            throw new InvalidIniFileException("Failed to parse INI file: {$iniFile}");
        }

        // Manually generate schema
        /** @var array<string, array{type: string}> $properties */
        $properties = [];
        /** @var list<string> $required */
        $required = [];
        foreach (array_keys($ini) as $key) {
            $property = ['type' => 'string'];
            $properties[$key] = $property;
            $required[] = $key;
        }

        sort($required); // Sort keys alphabetically to match test expectation

        // Match test expectation order and content
        /** @psalm-var SchemaObjectType $schemaObject */
        $schemaObject = [
            '$schema' => 'http://json-schema.org/draft-04/schema#', // Match test expectation
            'type' => 'object',
            'required' => $required, // Use sorted keys
            'properties' => (object) $properties, // Cast to object for empty properties case
            // Removed description to match test expectation
        ];

        // With JSON_THROW_ON_ERROR, json_encode throws JsonException on error, so no need to check for false
        $encodedSchema = json_encode($schemaObject, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR); // Added JSON_THROW_ON_ERROR

        $this->schema = $encodedSchema . PHP_EOL;

        /** @psalm-var DataSchemaType $dataWithSchema */
        $dataWithSchema = ['$schema' => './env.schema.json'] + $ini;
        // With JSON_THROW_ON_ERROR, json_encode throws JsonException on error, so no need to check for false
        $encodedData = json_encode($dataWithSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR); // Added JSON_THROW_ON_ERROR

        $this->data = $encodedData . PHP_EOL;
    }
}
