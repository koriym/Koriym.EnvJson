<?php

declare(strict_types=1);

namespace Koriym\EnvJson;

use JSONSchemaGenerator\Generator;
use Koriym\EnvJson\Exception\RuntimeException;

use function basename;
use function json_decode;
use function json_encode;
use function json_last_error;
use function parse_ini_file;
use function sprintf;

use const JSON_ERROR_NONE;
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
            throw new RuntimeException("Failed to parse INI file: {$iniFile}"); // @codeCoverageIgnore
        }

        // JSON_THROW_ON_ERROR makes json_encode throw on error, so no need to check for false here
        $jsonForSchema = json_encode($ini, JSON_THROW_ON_ERROR);

        $schema = Generator::fromJson($jsonForSchema, [
            'description' => sprintf('Generated from %s', basename($iniFile)),
        ]);

        // json_decode can return null/false/array/object, json_encode expects array/object
        $decodedSchema = json_decode($schema);
        /** @psalm-suppress ImpureFunctionCall */
        if ($decodedSchema === null && json_last_error() !== JSON_ERROR_NONE) {
             throw new RuntimeException('Failed to decode generated schema JSON'); // @codeCoverageIgnore
        }

        $encodedSchema = json_encode($decodedSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($encodedSchema === false) {
            throw new RuntimeException('Failed to encode schema JSON'); // @codeCoverageIgnore
        }

        $this->schema = $encodedSchema . PHP_EOL;

        $dataWithSchema = ['$schema' => './env.schema.json'] + $ini;
        $encodedData = json_encode($dataWithSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($encodedData === false) {
            throw new RuntimeException('Failed to encode data JSON'); // @codeCoverageIgnore
        }

        $this->data = $encodedData . PHP_EOL;
    }
}
