<?php

declare(strict_types=1);

namespace Koriym\EnvJson;

use Koriym\EnvJson\Exception\InvalidEnvJsonException;
use Koriym\EnvJson\Exception\JsonFileNotFoundException;
use Koriym\EnvJson\Exception\SchemaFileNotFoundException;
use PHPUnit\Framework\TestCase;

use function getenv;

class EnvJsonTest extends TestCase
{
    /** @var EnvJson */
    protected $envJson;

    protected function setUp(): void
    {
        $this->envJson = new EnvJson();
    }

    public function testIsInstanceOfEnvJson(): void
    {
        $actual = $this->envJson;
        $this->assertInstanceOf(EnvJson::class, $actual);
    }

    public function testLoadJson(): void
    {
        $this->envJson->load(__DIR__ . '/env/foo');
        $this->assertSame('foo-val', getenv('FOO'));
        $this->assertSame('bar-val', getenv('BAR'));
    }

    /**
     * @depends testLoadJson
     */
    public function testLoadEnv(): void
    {
        $this->envJson->load(__DIR__ . '/env/foo-no-json');
        $this->assertSame('foo-val', getenv('FOO'));
        $this->assertSame('bar-val', getenv('BAR'));
    }

    /**
     * @depends testLoadJson
     */
    public function testError(): void
    {
        $this->expectException(InvalidEnvJsonException::class);
        $this->envJson->load(__DIR__ . '/env/foo-error');
    }

    public function testNoSchemaFile(): void
    {
        $this->expectException(SchemaFileNotFoundException::class);
        $this->envJson->load(__DIR__ . '/env/no-schema');
    }

    public function testLoadDist(): void
    {
        $this->envJson->load(__DIR__ . '/env/foo-dist');
        $this->assertSame('dist-val', getenv('DIST'));
    }
}
