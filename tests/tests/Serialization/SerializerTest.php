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
use function unserialize;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Medium;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData;
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

    public function testStripPharPrefixRemovesPharPrefixFromObjectClassNamesAndPrivatePropertyKeys(): void
    {
        // When Serializer runs inside a PHAR, PHP serializes class names with a PHAR prefix
        // (e.g. PHPUnitPHAR\SebastianBergmann\...). Serializer::stripPharPrefix() removes those
        // prefixes so the resulting coverage file can be deserialized outside the PHAR.
        $pharPrefixedSerialized = 'O:73:"PHPUnitPHAR\SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData":2:{'
            . 's:87:"' . "\x00" . 'PHPUnitPHAR\SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData' . "\x00" . 'lineCoverage";a:0:{}'
            . 's:91:"' . "\x00" . 'PHPUnitPHAR\SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData' . "\x00" . 'functionCoverage";a:0:{}'
            . '}';

        $strippedSerialized = 'O:61:"SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData":2:{'
            . 's:75:"' . "\x00" . 'SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData' . "\x00" . 'lineCoverage";a:0:{}'
            . 's:79:"' . "\x00" . 'SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData' . "\x00" . 'functionCoverage";a:0:{}'
            . '}';

        $allowedClasses = ['allowed_classes' => [ProcessedCodeCoverageData::class]];

        $this->assertNotInstanceOf(
            ProcessedCodeCoverageData::class,
            unserialize($pharPrefixedSerialized, $allowedClasses),
        );

        $this->assertInstanceOf(
            ProcessedCodeCoverageData::class,
            unserialize($strippedSerialized, $allowedClasses),
        );
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
}
