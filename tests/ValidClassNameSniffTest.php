<?php

/**
 * Tests for the shared class-name sniff.
 */

declare(strict_types=1);

namespace Timmit\Phpcs\Rules\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ValidClassNameSniffTest extends TestCase
{
    /**
     * @param string $source
     */
    #[DataProvider('validSources')]
    public function testValidNameIsQuiet(string $source): void
    {
        $this->assertSame([], $this->sources($source));
    }

    /**
     * @param string   $source
     * @param string[] $expected
     */
    #[DataProvider('invalidSources')]
    public function testInvalidNameIsReported(string $source, array $expected): void
    {
        $this->assertSame($expected, $this->sources($source));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function validSources(): array
    {
        return [
            'abstract aItem' => ["<?php\nnamespace Example;\nabstract class aItem {}\n"],
            'abstract aConnector' => ["<?php\nnamespace Example;\nabstract class aConnector {}\n"],
            'abstract readonly aItem' => ["<?php\nnamespace Example;\nabstract readonly class aItem {}\n"],
            'trait tItem' => ["<?php\nnamespace Example;\ntrait tItem {}\n"],
            'interface iProvider' => ["<?php\nnamespace Example;\ninterface iProvider {}\n"],
            'interface iCorrespondence' => ["<?php\nnamespace Example;\ninterface iCorrespondence {}\n"],
            'class Sim' => ["<?php\nnamespace Example;\nclass Sim {}\n"],
            'enum Status' => ["<?php\nnamespace Example;\nenum Status {}\n"],
            'anonymous class' => ["<?php\nnamespace Example;\n\$object = new class {};\n"],
        ];
    }

    /**
     * @return array<string, array{string, string[]}>
     */
    public static function invalidSources(): array
    {
        return [
            'abstract without prefix' => [
                "<?php\nnamespace Example;\nabstract class Item {}\n",
                ['TiMMiT.Classes.ValidClassName.AbstractPrefix'],
            ],
            'abstract lowercase rest' => [
                "<?php\nnamespace Example;\nabstract class aitem {}\n",
                ['TiMMiT.Classes.ValidClassName.AbstractPrefix'],
            ],
            'trait without prefix' => [
                "<?php\nnamespace Example;\ntrait ItemTrait {}\n",
                ['TiMMiT.Classes.ValidClassName.TraitPrefix'],
            ],
            'trait lowercase rest' => [
                "<?php\nnamespace Example;\ntrait titem {}\n",
                ['TiMMiT.Classes.ValidClassName.TraitPrefix'],
            ],
            'interface without prefix' => [
                "<?php\nnamespace Example;\ninterface Connector {}\n",
                ['TiMMiT.Classes.ValidClassName.InterfacePrefix'],
            ],
            'interface lowercase rest' => [
                "<?php\nnamespace Example;\ninterface iprovider {}\n",
                ['TiMMiT.Classes.ValidClassName.InterfacePrefix'],
            ],
            'concrete lowercase' => [
                "<?php\nnamespace Example;\nclass sim {}\n",
                ['TiMMiT.Classes.ValidClassName.NotPascalCase'],
            ],
            'mixed file' => [
                "<?php\nnamespace Example;\nabstract class Item {}\nclass sim {}\ntrait ItemTrait {}\ninterface Connector {}\n",
                [
                    'TiMMiT.Classes.ValidClassName.AbstractPrefix',
                    'TiMMiT.Classes.ValidClassName.NotPascalCase',
                    'TiMMiT.Classes.ValidClassName.TraitPrefix',
                    'TiMMiT.Classes.ValidClassName.InterfacePrefix',
                ],
            ],
        ];
    }

    /**
     * @param string $source
     *
     * @return string[]
     */
    private function sources(string $source): array
    {
        $path = tempnam(sys_get_temp_dir(), 'phpcs');
        $phpPath = $path . '.php';
        rename($path, $phpPath);
        file_put_contents($phpPath, $source);

        $command = escapeshellarg(PHP_BINARY)
            . ' ' . escapeshellarg(dirname(__DIR__) . '/vendor/bin/phpcs')
            . ' --standard=TiMMiT --report=json --no-cache '
            . escapeshellarg($phpPath);

        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);
        unlink($phpPath);

        $report = json_decode(implode("\n", $output), true);
        $this->assertIsArray($report, 'phpcs exit ' . $exitCode . ': ' . implode("\n", $output));

        return $this->messageSources($report);
    }

    /**
     * @param mixed[] $report
     *
     * @return string[]
     */
    private function messageSources(array $report): array
    {
        $sources = [];
        foreach ($report['files'] as $file) {
            $this->collectFileSources($file['messages'], $sources);
        }

        return $sources;
    }

    /**
     * @param mixed[]  $messages
     * @param string[] $sources
     *
     * @return void
     */
    private function collectFileSources(array $messages, array &$sources): void
    {
        foreach ($messages as $message) {
            $sources[] = $message['source'];
        }
    }
}
