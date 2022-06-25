<?php

declare(strict_types=1);

namespace Koriym\EnvJson;

use PHPUnit\Framework\TestCase;

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
}
