<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage;

use function fclose;
use function fgets;
use function fopen;
use function trim;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Medium;

#[CoversClass(Serializer::class)]
#[CoversMethod(CodeCoverage::class, 'configuration')]
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
        $this->assertArrayHasKey('configuration', $data);
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

    public function testSerializedDataPreservesConfiguration(): void
    {
        $coverage = $this->getLineCoverageForBankAccount();
        $target   = TEST_FILES_PATH . 'tmp/serialized.php';

        (new Serializer)->serialize($target, $coverage);

        $data = require $target;

        $this->assertEquals($coverage->configuration(), $data['configuration']);
    }

    public function testSerializationWorksWhenDataContainsSingleQuotes(): void
    {
        $coverage = $this->getLineCoverageForFileWithEval();
        $target   = TEST_FILES_PATH . 'tmp/serialized.php';

        (new Serializer)->serialize($target, $coverage);

        $data = require $target;

        $this->assertEquals($coverage->getData(), $data['codeCoverage']);
    }
}
