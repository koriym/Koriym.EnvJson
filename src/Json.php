<?php

declare(strict_types=1);

namespace Koriym\EnvJson;

use JSONSchemaGenerator\Generator;
use Koriym\EnvJson\Exception\InvalidIniFileException;

use function basename;
use function json_decode;
use function json_encode;
use function parse_ini_file;
use function sprintf;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const PHP_EOL;

// Removed @psalm-immutable due to impure json_last_error() call
final class Json
{
    public string $data;
    public string $schema;

    public function __construct(string $iniFile)
    {
        $ini = parse_ini_file($iniFile);
        if ($ini === false) {
            throw new InvalidIniFileException("Failed to parse INI file: {$iniFile}");
        }

        // JSON_THROW_ON_ERROR makes json_encode throw on error, so no need to check for false here
        $jsonForSchema = json_encode($ini, JSON_THROW_ON_ERROR);

        $schema = Generator::fromJson($jsonForSchema, [
            'description' => sprintf('Generated from %s', basename($iniFile)),
        ]);

        // json_decode can return null/false/array/object, json_encode expects array/object
        // With JSON_THROW_ON_ERROR, json_decode throws JsonException on error, so no need to check json_last_error()
        $decodedSchema = json_decode($schema, false, 512, JSON_THROW_ON_ERROR); // Added JSON_THROW_ON_ERROR

        // With JSON_THROW_ON_ERROR, json_encode throws JsonException on error, so no need to check for false
        $encodedSchema = json_encode($decodedSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR); // Added JSON_THROW_ON_ERROR

        $this->schema = $encodedSchema . PHP_EOL;

        $dataWithSchema = ['$schema' => './env.schema.json'] + $ini;
        // With JSON_THROW_ON_ERROR, json_encode throws JsonException on error, so no need to check for false
        $encodedData = json_encode($dataWithSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR); // Added JSON_THROW_ON_ERROR

        $this->data = $encodedData . PHP_EOL;
    }
}
