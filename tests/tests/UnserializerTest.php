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

use function file_get_contents;
use function file_put_contents;
use function preg_replace;
use function serialize;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData;

#[CoversClass(Unserializer::class)]
#[Medium]
final class UnserializerTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->removeTemporaryFiles();
    }

    public function testRoundTrip(): void
    {
        $coverage = $this->getLineCoverageForBankAccount();
        $target   = TEST_FILES_PATH . 'tmp/serialized.php';

        (new Serializer)->serialize($target, $coverage);

        $result = (new Unserializer)->unserialize($target);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('buildInformation', $result);
        $this->assertArrayHasKey('codeCoverage', $result);
        $this->assertArrayHasKey('testResults', $result);
        $this->assertInstanceOf(ProcessedCodeCoverageData::class, $result['codeCoverage']);
        $this->assertEquals($coverage->getData(), $result['codeCoverage']);
        $this->assertEquals($coverage->getTests(), $result['testResults']);
    }

    public function testThrowsExceptionWhenFileDoesNotExist(): void
    {
        $this->expectException(FileCouldNotBeReadException::class);

        (new Unserializer)->unserialize('/nonexistent/path.php');
    }

    public function testThrowsExceptionWhenFileIsEmpty(): void
    {
        $target = TEST_FILES_PATH . 'tmp/serialized.php';

        file_put_contents($target, '');

        $this->expectException(FileCouldNotBeReadException::class);

        (new Unserializer)->unserialize($target);
    }

    public function testThrowsExceptionWhenFileHasNoVersionHeader(): void
    {
        $target = TEST_FILES_PATH . 'tmp/serialized.php';

        file_put_contents($target, "<?php\nreturn [];\n");

        $this->expectException(FileCouldNotBeReadException::class);
        $this->expectExceptionMessage('does not contain phpunit/php-code-coverage version information');

        (new Unserializer)->unserialize($target);
    }

    public function testThrowsExceptionWhenVersionDoesNotMatch(): void
    {
        $coverage = $this->getLineCoverageForBankAccount();
        $target   = TEST_FILES_PATH . 'tmp/serialized.php';

        (new Serializer)->serialize($target, $coverage);

        $contents = file_get_contents($target);
        $tampered = preg_replace('/^(<\?php \/\/ phpunit\/php-code-coverage version ).+$/m', '${1}0.0.0', $contents);
        file_put_contents($target, $tampered);

        $this->expectException(VersionMismatchException::class);

        (new Unserializer)->unserialize($target);
    }

    public function testThrowsExceptionWhenDataHasInvalidShape(): void
    {
        $target = TEST_FILES_PATH . 'tmp/serialized.php';

        $header = '<?php // phpunit/php-code-coverage version ' . Version::id();
        file_put_contents(
            $target,
            $header . "\nreturn \unserialize(<<<'END_OF_COVERAGE_SERIALIZATION'\n" .
            serialize(['buildInformation' => [], 'codeCoverage' => null, 'testResults' => []]) . "\n" .
            "END_OF_COVERAGE_SERIALIZATION\n);",
        );

        $this->expectException(InvalidCoverageDataException::class);

        (new Unserializer)->unserialize($target);
    }

    public function testReturnedDataContainsBuildInformation(): void
    {
        $coverage = $this->getLineCoverageForBankAccount();
        $target   = TEST_FILES_PATH . 'tmp/serialized.php';

        (new Serializer)->serialize($target, $coverage);

        $result = (new Unserializer)->unserialize($target);

        $this->assertIsArray($result['buildInformation']);
        $this->assertArrayHasKey('timestamp', $result['buildInformation']);
        $this->assertArrayHasKey('runtime', $result['buildInformation']);
        $this->assertArrayHasKey('phpCodeCoverage', $result['buildInformation']);
        $this->assertEquals($coverage->driverInformation(), $result['buildInformation']['phpCodeCoverage']['driverInformation']);
    }

    public function testThrowsExceptionWhenDataIsNotAnArray(): void
    {
        $target = TEST_FILES_PATH . 'tmp/serialized.php';
        file_put_contents(
            $target,
            '<?php // phpunit/php-code-coverage version ' . Version::id() . "\nreturn 'not an array';",
        );

        $this->expectException(InvalidCoverageDataException::class);
        $this->expectExceptionMessage('Coverage data is not an array');

        (new Unserializer)->unserialize($target);
    }

    public function testThrowsExceptionWhenBuildInformationIsNotAnArray(): void
    {
        $target = TEST_FILES_PATH . 'tmp/serialized.php';
        file_put_contents(
            $target,
            '<?php // phpunit/php-code-coverage version ' . Version::id() . "\nreturn ['buildInformation' => 'invalid'];",
        );

        $this->expectException(InvalidCoverageDataException::class);
        $this->expectExceptionMessage("missing valid 'buildInformation' key");

        (new Unserializer)->unserialize($target);
    }

    public function testThrowsExceptionWhenRuntimeIsNotAnArray(): void
    {
        $target = TEST_FILES_PATH . 'tmp/serialized.php';
        file_put_contents(
            $target,
            '<?php // phpunit/php-code-coverage version ' . Version::id() . "\nreturn ['buildInformation' => ['timestamp' => '2024-01-01', 'runtime' => 'invalid']];",
        );

        $this->expectException(InvalidCoverageDataException::class);
        $this->expectExceptionMessage("missing valid 'buildInformation.runtime' key");

        (new Unserializer)->unserialize($target);
    }

    public function testThrowsExceptionWhenRuntimeSubkeyIsMissing(): void
    {
        $target = TEST_FILES_PATH . 'tmp/serialized.php';
        file_put_contents(
            $target,
            '<?php // phpunit/php-code-coverage version ' . Version::id() . "\nreturn ['buildInformation' => ['timestamp' => '2024-01-01', 'runtime' => []]];",
        );

        $this->expectException(InvalidCoverageDataException::class);
        $this->expectExceptionMessage("missing valid 'buildInformation.runtime.name' key");

        (new Unserializer)->unserialize($target);
    }

    public function testThrowsExceptionWhenPhpCodeCoverageIsNotAnArray(): void
    {
        $target = TEST_FILES_PATH . 'tmp/serialized.php';
        file_put_contents(
            $target,
            '<?php // phpunit/php-code-coverage version ' . Version::id() . "\nreturn ['buildInformation' => ['timestamp' => '2024-01-01', 'runtime' => ['name' => 'PHP', 'version' => '8.3', 'vendorUrl' => 'https://php.net'], 'phpCodeCoverage' => 'invalid']];",
        );

        $this->expectException(InvalidCoverageDataException::class);
        $this->expectExceptionMessage("missing valid 'buildInformation.phpCodeCoverage' key");

        (new Unserializer)->unserialize($target);
    }

    public function testThrowsExceptionWhenPhpCodeCoverageVersionIsMissing(): void
    {
        $target = TEST_FILES_PATH . 'tmp/serialized.php';
        file_put_contents(
            $target,
            '<?php // phpunit/php-code-coverage version ' . Version::id() . "\nreturn ['buildInformation' => ['timestamp' => '2024-01-01', 'runtime' => ['name' => 'PHP', 'version' => '8.3', 'vendorUrl' => 'https://php.net'], 'phpCodeCoverage' => []]];",
        );

        $this->expectException(InvalidCoverageDataException::class);
        $this->expectExceptionMessage("missing valid 'buildInformation.phpCodeCoverage.version' key");

        (new Unserializer)->unserialize($target);
    }

    public function testThrowsExceptionWhenDriverInformationIsNotAnArray(): void
    {
        $target = TEST_FILES_PATH . 'tmp/serialized.php';
        file_put_contents(
            $target,
            '<?php // phpunit/php-code-coverage version ' . Version::id() . "\nreturn ['buildInformation' => ['timestamp' => '2024-01-01', 'runtime' => ['name' => 'PHP', 'version' => '8.3', 'vendorUrl' => 'https://php.net'], 'phpCodeCoverage' => ['version' => '12.0.0', 'driverInformation' => 'invalid']]];",
        );

        $this->expectException(InvalidCoverageDataException::class);
        $this->expectExceptionMessage("missing valid 'buildInformation.phpCodeCoverage.driverInformation' key");

        (new Unserializer)->unserialize($target);
    }

    public function testThrowsExceptionWhenDriverInformationSubkeyIsMissing(): void
    {
        $target = TEST_FILES_PATH . 'tmp/serialized.php';
        file_put_contents(
            $target,
            '<?php // phpunit/php-code-coverage version ' . Version::id() . "\nreturn ['buildInformation' => ['timestamp' => '2024-01-01', 'runtime' => ['name' => 'PHP', 'version' => '8.3', 'vendorUrl' => 'https://php.net'], 'phpCodeCoverage' => ['version' => '12.0.0', 'driverInformation' => []]]];",
        );

        $this->expectException(InvalidCoverageDataException::class);
        $this->expectExceptionMessage("missing valid 'buildInformation.phpCodeCoverage.driverInformation.name' key");

        (new Unserializer)->unserialize($target);
    }

    public function testThrowsExceptionWhenCodeCoverageIsNotProcessedCodeCoverageData(): void
    {
        $target = TEST_FILES_PATH . 'tmp/serialized.php';
        file_put_contents(
            $target,
            '<?php // phpunit/php-code-coverage version ' . Version::id() . "\nreturn ['buildInformation' => ['timestamp' => '2024-01-01', 'runtime' => ['name' => 'PHP', 'version' => '8.3', 'vendorUrl' => 'https://php.net'], 'phpCodeCoverage' => ['version' => '12.0.0', 'driverInformation' => ['name' => 'Xdebug', 'version' => '3.0.0']]], 'codeCoverage' => null, 'testResults' => []];",
        );

        $this->expectException(InvalidCoverageDataException::class);
        $this->expectExceptionMessage("missing valid 'codeCoverage' key");

        (new Unserializer)->unserialize($target);
    }

    public function testThrowsExceptionWhenTestResultsIsNotAnArray(): void
    {
        $target = TEST_FILES_PATH . 'tmp/serialized.php';

        $data = [
            'buildInformation' => [
                'timestamp'       => '2024-01-01',
                'runtime'         => ['name' => 'PHP', 'version' => '8.3', 'vendorUrl' => 'https://php.net'],
                'phpCodeCoverage' => ['version' => '12.0.0', 'driverInformation' => ['name' => 'Xdebug', 'version' => '3.0.0']],
            ],
            'codeCoverage' => new ProcessedCodeCoverageData,
            'testResults'  => 'not an array',
        ];

        $header = '<?php // phpunit/php-code-coverage version ' . Version::id();
        file_put_contents(
            $target,
            $header . "\nreturn \unserialize(<<<'END_OF_COVERAGE_SERIALIZATION'\n" .
            serialize($data) . "\n" .
            "END_OF_COVERAGE_SERIALIZATION\n);",
        );

        $this->expectException(InvalidCoverageDataException::class);
        $this->expectExceptionMessage("missing valid 'testResults' key");

        (new Unserializer)->unserialize($target);
    }
}
