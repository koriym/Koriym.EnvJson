<?php

declare(strict_types=1);

namespace Koriym\EnvJson;

use Koriym\EnvJson\Exception\SchemaFileNotFoundException;

use function array_keys;
use function assert;
use function file_exists;
use function getenv;
use function putenv;
use function str_replace;

final class EnvJson
{
    /** @var JsonLoad */
    private $jsonLoad;

    /** @var EnvLoad */
    private $envLoad;

    public function __construct()
    {
        $this->jsonLoad = new JsonLoad();
        $this->envLoad = new EnvLoad();
    }

    public function load(string $dir): void
    {
        $envJson = $dir . '/env.json';
        $data = $this->getEnvValue($envJson);
        foreach ($data as $key => $val) { // @phpstan-ignore-line
            putenv("{$key}={$val}");
        }
    }

    private function getEnvValue(string $envJson): object
    {
        $schemaJson = str_replace('.json', '.schema.json', $envJson);
        if (! file_exists($schemaJson)) {
            throw new SchemaFileNotFoundException($schemaJson);
        }

        $schema = ($this->jsonLoad)($schemaJson);
        $data = $this->loadData($envJson, $schema);
        (new Validate())($data, $schema);

        return $data;
    }

    private function loadData(string $envJson, object $schema): object
    {
        assert(isset($schema->properties));
        $firstPropName = array_keys((array) ($schema->properties))[0];
        $firstProp = getenv($firstPropName);
        if ($firstProp) {
            return ($this->envLoad)();
        }

        return ($this->jsonLoad)($envJson);
    }
}
