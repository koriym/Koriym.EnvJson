<?php

declare(strict_types=1);

namespace Koriym\EnvJson;

use Koriym\EnvJson\Exception\InvalidEnvJsonException;
use Koriym\EnvJson\Exception\SchemaFileNotFoundException;
use PHPUnit\Framework\TestCase;
use stdClass;

use function getenv;
use function putenv;

class EnvJsonTest extends TestCase
{
    public function testLoadJson(): void
    {
        (new EnvJson())->load(__DIR__ . '/env/foo');
        $this->assertSame('foo-val', getenv('FOO'));
        $this->assertSame('bar-val', getenv('BAR'));
    }

    /** @depends testLoadJson */
    public function testLoadEnv(): void
    {
        (new EnvJson())->load(__DIR__ . '/env/foo-no-json');
        $this->assertSame('foo-val', getenv('FOO'));
        $this->assertSame('bar-val', getenv('BAR'));
    }

    /** @depends testLoadJson */
    public function testEnvFileNotFoundError(): void
    {
        // EnvJson::load now returns empty stdClass when env file not found
        // instead of throwing EnvJsonFileNotFoundException
        $result = (new EnvJson())->load(__DIR__ . '/env/file-not-found-error');
        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEmpty((array) $result); // Check if the object has no properties
    }

    /** @depends testLoadJson */
    public function testInvalidError(): void
    {
        putenv('FOO');
        putenv('BAR');
        $this->expectException(InvalidEnvJsonException::class);
        (new EnvJson())->load(__DIR__ . '/env/invalid-error');
    }

    public function testNoSchemaFile(): void
    {
        $this->expectException(SchemaFileNotFoundException::class);
        (new EnvJson())->load(__DIR__ . '/env/no-schema');
    }

    public function testLoadDist(): void
    {
        (new EnvJson())->load(__DIR__ . '/env/foo-dist');
        $this->assertSame('dist-val', getenv('DIST'));
    }
}
