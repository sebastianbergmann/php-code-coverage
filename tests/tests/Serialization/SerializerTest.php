<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Serialization;

use const DIRECTORY_SEPARATOR;
use function count;
use function fclose;
use function fgets;
use function fopen;
use function trim;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Medium;
use ReflectionMethod;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\TestCase;

#[CoversClass(Serializer::class)]
#[CoversMethod(CodeCoverage::class, 'driverInformation')]
#[Medium]
final class SerializerTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->removeTemporaryFiles();
    }

    public function testSerializedFileHasVersionHeaderOnFirstLine(): void
    {
        $coverage = $this->getLineCoverageForBankAccount();
        $target   = TEST_FILES_PATH . 'tmp/serialized.php';

        (new Serializer)->serialize($target, $coverage);

        $file      = fopen($target, 'r');
        $firstLine = fgets($file);
        fclose($file);

        $this->assertMatchesRegularExpression(
            '/^<\?php \/\/ phpunit\/php-code-coverage version .+$/',
            trim($firstLine),
        );
    }

    public function testSerializedFileIsValidPhp(): void
    {
        $coverage = $this->getLineCoverageForBankAccount();
        $target   = TEST_FILES_PATH . 'tmp/serialized.php';

        (new Serializer)->serialize($target, $coverage);

        $data = require $target;

        $this->assertIsArray($data);
        $this->assertArrayHasKey('buildInformation', $data);
        $this->assertArrayHasKey('basePath', $data);
        $this->assertArrayHasKey('codeCoverage', $data);
        $this->assertArrayHasKey('testResults', $data);
    }

    public function testSerializedDataHasBasePath(): void
    {
        $coverage = $this->getLineCoverageForBankAccount();
        $target   = TEST_FILES_PATH . 'tmp/serialized.php';

        (new Serializer)->serialize($target, $coverage);

        $data = require $target;

        $this->assertIsString($data['basePath']);
        $this->assertNotEmpty($data['basePath']);
    }

    public function testSerializedDataPreservesCoverageData(): void
    {
        $coverage = $this->getLineCoverageForBankAccount();
        $target   = TEST_FILES_PATH . 'tmp/serialized.php';

        (new Serializer)->serialize($target, $coverage);

        $data = require $target;

        $originalFiles   = $coverage->getData()->coveredFiles();
        $serialisedFiles = $data['codeCoverage']->coveredFiles();

        $this->assertCount(count($originalFiles), $serialisedFiles);

        foreach ($serialisedFiles as $i => $relativeFile) {
            $this->assertSame(
                $originalFiles[$i],
                $data['basePath'] . DIRECTORY_SEPARATOR . $relativeFile,
            );
        }
    }

    public function testSerializedDataPreservesTestResults(): void
    {
        $coverage = $this->getLineCoverageForBankAccount();
        $target   = TEST_FILES_PATH . 'tmp/serialized.php';

        (new Serializer)->serialize($target, $coverage);

        $data = require $target;

        $this->assertEquals($coverage->getTests(), $data['testResults']);
    }

    public function testSerializedDataPreservesBuildInformation(): void
    {
        $coverage = $this->getLineCoverageForBankAccount();
        $target   = TEST_FILES_PATH . 'tmp/serialized.php';

        (new Serializer)->serialize($target, $coverage);

        $data = require $target;

        $this->assertIsArray($data['buildInformation']);
        $this->assertArrayHasKey('timestamp', $data['buildInformation']);
        $this->assertArrayHasKey('runtime', $data['buildInformation']);
        $this->assertArrayHasKey('phpCodeCoverage', $data['buildInformation']);
        $this->assertEquals($coverage->driverInformation(), $data['buildInformation']['phpCodeCoverage']['driverInformation']);
    }

    public function testSerializationWorksWhenDataContainsSingleQuotes(): void
    {
        $coverage = $this->getLineCoverageForFileWithEval();
        $target   = TEST_FILES_PATH . 'tmp/serialized.php';

        (new Serializer)->serialize($target, $coverage);

        $data = require $target;

        $this->assertIsString($data['basePath']);
        $this->assertNotEmpty($data['basePath']);
        $this->assertCount(count($coverage->getData()->coveredFiles()), $data['codeCoverage']->coveredFiles());
    }

    public function testSerializedDataIncludesGitInformationWhenRequested(): void
    {
        $coverage = $this->getLineCoverageForBankAccount();
        $target   = TEST_FILES_PATH . 'tmp/serialized.php';

        (new Serializer)->serialize($target, $coverage, true);

        $data = require $target;

        $this->assertIsArray($data['buildInformation']);

        if (isset($data['buildInformation']['git'])) {
            $this->assertArrayHasKey('originUrl', $data['buildInformation']['git']);
            $this->assertArrayHasKey('branch', $data['buildInformation']['git']);
            $this->assertArrayHasKey('commit', $data['buildInformation']['git']);
            $this->assertArrayHasKey('isClean', $data['buildInformation']['git']);
            $this->assertArrayHasKey('status', $data['buildInformation']['git']);
        }
    }

    public function testStripPharPrefixRemovesPHPUnitPHARNamespacePrefix(): void
    {
        $method = new ReflectionMethod(Serializer::class, 'stripPharPrefix');

        $serialized = 'O:45:"PHPUnitPHAR\SebastianBergmann\CodeCoverage\Foo":0:{}';
        $expected   = 'O:33:"SebastianBergmann\CodeCoverage\Foo":0:{}';

        $this->assertSame($expected, $method->invoke(new Serializer, $serialized));
    }

    public function testStripPharPrefixHandlesProtectedAndPrivateProperties(): void
    {
        $method = new ReflectionMethod(Serializer::class, 'stripPharPrefix');

        $serialized = 's:50:"' . "\x00" . 'PHPUnitPHAR\SebastianBergmann\CodeCoverage\Foo"';
        $expected   = 's:38:"' . "\x00" . 'SebastianBergmann\CodeCoverage\Foo"';

        $this->assertSame($expected, $method->invoke(new Serializer, $serialized));
    }

    public function testStripPharPrefixHandlesMultipleOccurrences(): void
    {
        $method = new ReflectionMethod(Serializer::class, 'stripPharPrefix');

        $serialized = 'O:16:"PHPUnitPHAR\Foo":1:{s:16:"PHPUnitPHAR\Bar";}';
        $expected   = 'O:4:"Foo":1:{s:4:"Bar";}';

        $this->assertSame($expected, $method->invoke(new Serializer, $serialized));
    }

    public function testStripPharPrefixDoesNotModifyStringsWithoutPrefix(): void
    {
        $method = new ReflectionMethod(Serializer::class, 'stripPharPrefix');

        $serialized = 'O:33:"SebastianBergmann\CodeCoverage\Foo":0:{}';

        $this->assertSame($serialized, $method->invoke(new Serializer, $serialized));
    }
}
