<?php

declare(strict_types=1);

namespace Koriym\EnvJson;

use Koriym\EnvJson\Exception\InvalidEnvJsonException;
use Koriym\EnvJson\Exception\SchemaFileNotFoundException;
use PHPUnit\Framework\TestCase;

use function getenv;
use function putenv;

class EnvJsonTest extends TestCase
{
    public function testLoadJson(): void
    {
        (new EnvJson(__DIR__ . '/env/foo'))->load();
        $this->assertSame('foo-val', getenv('FOO'));
        $this->assertSame('bar-val', getenv('BAR'));
    }

    /**
     * @depends testLoadJson
     */
    public function testLoadEnv(): void
    {
        (new EnvJson(__DIR__ . '/env/foo-no-json'))->load();
        $this->assertSame('foo-val', getenv('FOO'));
        $this->assertSame('bar-val', getenv('BAR'));
    }

    /**
     * @depends testLoadJson
     */
    public function testError(): void
    {
        putenv('FOO');
        putenv('BAR');
        $this->expectException(InvalidEnvJsonException::class);
        (new EnvJson(__DIR__ . '/env/foo-error'))->load();
    }

    public function testNoSchemaFile(): void
    {
        $this->expectException(SchemaFileNotFoundException::class);
        (new EnvJson(__DIR__ . '/env/no-schema'));
    }

    public function testLoadDist(): void
    {
        (new EnvJson(__DIR__ . '/env/foo-dist'))->load();
        $this->assertSame('dist-val', getenv('DIST'));
    }
}
