<?php

declare(strict_types=1);

namespace Koriym\EnvJson;

use JSONSchemaGenerator\Generator;

use function basename;
use function json_decode;
use function json_encode;
use function parse_ini_file;
use function sprintf;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const PHP_EOL;

/** @psalm-immutable  */
final class Json
{
    public string $data;
    public string $schema;

    public function __construct(string $iniFile)
    {
        $ini = parse_ini_file($iniFile);
        $jsonForSchema = json_encode($ini, JSON_THROW_ON_ERROR);

        $schema = Generator::fromJson($jsonForSchema, [
            'description' => sprintf('Generated from %s', basename($iniFile)),
        ]);
        $this->schema = json_encode(json_decode($schema), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

        $schemaPath = sprintf('./%s.schema.json', basename($iniFile));
        $dataWithSchema = ['$schema' => $schemaPath] + $ini;
        $this->data = json_encode($dataWithSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }
}
