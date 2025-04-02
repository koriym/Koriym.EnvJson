<?php

declare(strict_types=1);

namespace Koriym\EnvJson;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function bin2hex;
use function chdir;
use function escapeshellarg;
use function exec;
use function file_put_contents;
use function getcwd;
use function implode;
use function is_dir;
use function json_encode;
use function mkdir;
use function random_bytes;
use function realpath;
use function rmdir;
use function sprintf;
use function sys_get_temp_dir;
use function unlink;

use const JSON_PRETTY_PRINT;

class BinEnvJsonTest extends TestCase
{
    private string $scriptPath;
    private string $originalDir;
    private string $tempDir;

    protected function setUp(): void
    {
        $scriptPath = realpath(__DIR__ . '/../bin/envjson');
        if ($scriptPath === false) {
            $this->fail('bin/envjson script not found.');
        }

        $this->scriptPath = $scriptPath;

        $originalDir = getcwd();
        if ($originalDir === false) {
            $this->fail('Could not get current working directory.');
        }

        $this->originalDir = $originalDir;

        $this->tempDir = sys_get_temp_dir() . '/envjson_test_' . bin2hex(random_bytes(5));
        if (! mkdir($this->tempDir, 0777, true) && ! is_dir($this->tempDir)) {
            $this->fail(sprintf('Directory "%s" was not created', $this->tempDir));
        }

        chdir($this->tempDir); // Change to temp dir for default behavior tests
    }

    protected function tearDown(): void
    {
        chdir($this->originalDir);
        // Clean up the temporary directory
        if (isset($this->tempDir) && is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
    }

    /**
     * Recursively remove a directory and its contents.
     */
    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        /** @var SplFileInfo $item */
        foreach ($items as $item) {
            $realPath = $item->getRealPath();
            if ($realPath === false) {
                // Handle error or skip if path is invalid
                continue;
            }

            if ($item->isDir() && ! $item->isLink()) {
                rmdir($realPath);
            } else {
                unlink($realPath);
            }
        }

        rmdir($dir);
    }

    /** @return array{output: string, code: int} */
    private function executeScript(string $options = ''): array
    {
        $command = sprintf('php %s %s 2>&1', escapeshellarg($this->scriptPath), $options);
        exec($command, $output, $returnCode);

        return ['output' => implode("\n", $output), 'code' => $returnCode];
    }

    public function testDefaultBehavior(): void
    {
        // Create dummy schema and env files in the temp directory
        $schemaContent = json_encode([
            '$schema' => 'http://json-schema.org/draft-07/schema#',
            'type' => 'object',
            'required' => ['DEFAULT_VAR'],
            'properties' => ['DEFAULT_VAR' => ['type' => 'string']],
        ], JSON_PRETTY_PRINT);
        file_put_contents($this->tempDir . '/env.schema.json', $schemaContent);

        $envContent = json_encode([
            '$schema' => './env.schema.json',
            'DEFAULT_VAR' => 'default_value',
        ], JSON_PRETTY_PRINT);
        file_put_contents($this->tempDir . '/env.json', $envContent);

        // Execute script without options (should use current dir: tempDir)
        $result = $this->executeScript();
        $expectedOutput = <<<'EOT'
export DEFAULT_VAR="default_value"
EOT;
        $this->assertSame(0, $result['code']);
        $this->assertSame($expectedOutput, $result['output']);
    }

    public function testSpecifyDirectoryWithOptionD(): void
    {
        $result = $this->executeScript('-d ' . escapeshellarg(__DIR__ . '/../demo/env-json-1'));
        $expectedOutput = <<<'EOT'
export FOO="foo1"
export BAR="bar1"
EOT;
        $this->assertSame(0, $result['code']);
        $this->assertSame($expectedOutput, $result['output']);
    }

    public function testSpecifyDirectoryWithEnvDistJson(): void
    {
        // This is essentially the same as testSpecifyDirectoryWithOptionD for demo/env-json-1
        // as it only contains env.dist.json
        $result = $this->executeScript('-d ' . escapeshellarg(__DIR__ . '/../demo/env-json-1'));
        $expectedOutput = <<<'EOT'
export FOO="foo1"
export BAR="bar1"
EOT;
        $this->assertSame(0, $result['code']);
        $this->assertSame($expectedOutput, $result['output']);
    }

    public function testSpecifyDirectoryWithEnvJson(): void
    {
        $result = $this->executeScript('-d ' . escapeshellarg(__DIR__ . '/../demo/env-json-2'));
        $expectedOutput = <<<'EOT'
export FOO="foo2"
export BAR="bar2"
EOT;
        $this->assertSame(0, $result['code']);
        $this->assertSame($expectedOutput, $result['output']);
    }

    public function testSpecifyFileWithOptionF(): void
    {
        // Create schema and a custom-named env file
        $schemaContent = json_encode([
            '$schema' => 'http://json-schema.org/draft-07/schema#',
            'type' => 'object',
            'required' => ['CUSTOM_FILE_VAR'],
            'properties' => ['CUSTOM_FILE_VAR' => ['type' => 'string']],
        ], JSON_PRETTY_PRINT);
        file_put_contents($this->tempDir . '/env.schema.json', $schemaContent);

        $envContent = json_encode([
            '$schema' => './env.schema.json',
            'CUSTOM_FILE_VAR' => 'custom_file_value',
        ], JSON_PRETTY_PRINT);
        $customFileName = 'custom.env.json';
        file_put_contents($this->tempDir . '/' . $customFileName, $envContent);

        // Execute script specifying the custom file name
        // Note: -d . is needed because the script constructs schema path relative to the dir
        $result = $this->executeScript('-d . -f ' . escapeshellarg($customFileName));
        // Script should correctly find env.schema.json in the specified directory (-d .)
        // even when -f is used.
        $expectedOutput = <<<'EOT'
export CUSTOM_FILE_VAR="custom_file_value"
EOT;
        $this->assertSame(0, $result['code'], 'Output: ' . $result['output']);
        $this->assertSame($expectedOutput, $result['output']);
    }

    public function testOutputFormatShell(): void
    {
        // Default format is shell, covered by other tests
        $result = $this->executeScript('-d ' . escapeshellarg(__DIR__ . '/../demo/env-json-1') . ' -o shell');
        $expectedOutput = <<<'EOT'
export FOO="foo1"
export BAR="bar1"
EOT;
        $this->assertSame(0, $result['code']);
        $this->assertSame($expectedOutput, $result['output']);
    }

    public function testOutputFormatFpm(): void
    {
        $result = $this->executeScript('-d ' . escapeshellarg(__DIR__ . '/../demo/env-json-1') . ' -o fpm');
        // Failure 2: Actual output has quotes
        $expectedOutput = <<<'EOT'
env[FOO] = "foo1"
env[BAR] = "bar1"
EOT;
        $this->assertSame(0, $result['code']);
        $this->assertSame($expectedOutput, $result['output']);
    }

    public function testOutputFormatIni(): void
    {
        $result = $this->executeScript('-d ' . escapeshellarg(__DIR__ . '/../demo/env-json-1') . ' -o ini');
        // Failure 3: Actual output has quotes and spaces
        $expectedOutput = <<<'EOT'
FOO = "foo1"
BAR = "bar1"
EOT;
        $this->assertSame(0, $result['code']);
        $this->assertSame($expectedOutput, $result['output']);
    }

    public function testSchemaFileNotFoundError(): void
    {
         // Test with a non-existent directory (schema will be missing)
        $result = $this->executeScript('-d ' . escapeshellarg(__DIR__ . '/non_existent_dir'));
        // Expect exit code 1 after script fix
        $this->assertSame(1, $result['code']);
        $this->assertStringContainsString('Warning: ', $result['output']); // Check for Warning
        $this->assertStringContainsString('non_existent_dir/env.schema.json', $result['output']); // Check for the problematic path
    }

    public function testEnvFileNotFoundButSchemaExists(): void
    {
        // Create only the schema file in the temp directory
        $schemaContent = json_encode([
            '$schema' => 'http://json-schema.org/draft-07/schema#',
            'type' => 'object',
            'properties' => ['SOME_VAR' => ['type' => 'string']], // Schema exists
        ], JSON_PRETTY_PRINT);
        file_put_contents($this->tempDir . '/env.schema.json', $schemaContent);

        // env.json and env.dist.json do not exist

        // Execute script, expecting success (code 0) and no output
        $result = $this->executeScript();
        $this->assertSame(0, $result['code'], 'Script should exit 0 when env file is missing but schema exists.');
        $this->assertSame('', $result['output'], 'Script should produce no output when env file is missing.');
    }

    public function testInvalidJsonError(): void
    {
        // Create a valid schema
        $schemaContent = json_encode([
            '$schema' => 'http://json-schema.org/draft-07/schema#',
            'type' => 'object',
            'required' => ['BAD_JSON'],
            'properties' => ['BAD_JSON' => ['type' => 'string']],
        ], JSON_PRETTY_PRINT);
        file_put_contents($this->tempDir . '/env.schema.json', $schemaContent);

        // Create an invalid JSON file (missing closing brace)
        $invalidJsonContent = <<<'JSON'
{
    "$schema": "./env.schema.json",
    "BAD_JSON": "some_value"

JSON;
        file_put_contents($this->tempDir . '/env.json', $invalidJsonContent);

        // Execute script, expecting an error
        $result = $this->executeScript();
        // Expect exit code 1 after script fix
        $this->assertSame(1, $result['code']);
        $this->assertStringContainsString('Warning: Syntax error', $result['output']);
    }

    public function testSchemaValidationError(): void
    {
        // Create a schema requiring 'REQUIRED_PROP'
        $schemaContent = json_encode([
            '$schema' => 'http://json-schema.org/draft-07/schema#',
            'type' => 'object',
            'required' => ['REQUIRED_PROP'],
            'properties' => [
                'REQUIRED_PROP' => ['type' => 'string'],
                'OPTIONAL_PROP' => ['type' => 'integer'],
            ],
        ], JSON_PRETTY_PRINT);
        file_put_contents($this->tempDir . '/env.schema.json', $schemaContent);

        // Create an env file missing the required property
        $invalidEnvContent = json_encode([
            '$schema' => './env.schema.json',
            'OPTIONAL_PROP' => 123,
            // REQUIRED_PROP is missing
        ], JSON_PRETTY_PRINT);
        file_put_contents($this->tempDir . '/env.json', $invalidEnvContent);

        // Execute script, expecting a schema validation error
        $result = $this->executeScript();
        // Expect exit code 1 after script fix
        $this->assertSame(1, $result['code']);
        $this->assertStringContainsString('Warning:', $result['output']); // Check for Warning
        $this->assertStringContainsString('REQUIRED_PROP', $result['output']); // Mention the missing prop
        $this->assertStringContainsString('is required', $result['output']); // Mention the reason
    }

    public function testVerboseOption(): void
    {
        // Check if verbose output contains specific messages
        $result = $this->executeScript('-d ' . escapeshellarg(__DIR__ . '/../demo/env-json-1') . ' -v');
        $this->assertSame(0, $result['code']);
        // Failure 7: Actual verbose output is different
        $this->assertStringContainsString('export FOO="foo1"', $result['output']);
        $this->assertStringContainsString('export BAR="bar1"', $result['output']);
        $this->assertStringContainsString('Successfully loaded', $result['output']); // Check for the success message added by -v
    }

    public function testQuietOption(): void
    {
        // Check if quiet suppresses warnings (e.g., file not found warnings if applicable)
        // For a successful run, output should be the same as non-quiet
        $result = $this->executeScript('-d ' . escapeshellarg(__DIR__ . '/../demo/env-json-1') . ' -q');
        $expectedOutput = <<<'EOT'
export FOO="foo1"
export BAR="bar1"
EOT;
        $this->assertSame(0, $result['code']);
        $this->assertSame($expectedOutput, $result['output']);

        // Add a test case for quiet suppressing actual warnings if possible
        // e.g., try loading a non-existent file with -q
        // This tests a missing SCHEMA file with -q
        $resultQuietError = $this->executeScript('-d ' . escapeshellarg(__DIR__ . '/non_existent_dir') . ' -q');
        // Expect exit code 1 after script fix, even with -q
        $this->assertSame(1, $resultQuietError['code']);
        $this->assertSame('', $resultQuietError['output']); // Expect no output on error with -q
    }
}
