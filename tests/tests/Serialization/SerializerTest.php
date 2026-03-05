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

use function fclose;
use function fgets;
use function fopen;
use function trim;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Medium;
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
        $this->assertArrayHasKey('codeCoverage', $data);
        $this->assertArrayHasKey('testResults', $data);
    }

    public function testSerializedDataPreservesCoverageData(): void
    {
        $coverage = $this->getLineCoverageForBankAccount();
        $target   = TEST_FILES_PATH . 'tmp/serialized.php';

        (new Serializer)->serialize($target, $coverage);

        $data = require $target;

        $this->assertEquals($coverage->getData(), $data['codeCoverage']);
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

        $this->assertEquals($coverage->getData(), $data['codeCoverage']);
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
