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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use ReflectionProperty;
use SebastianBergmann\CodeCoverage\Driver\NullDriver;

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

        $this->assertInstanceOf(CodeCoverage::class, $result);
        $this->assertEquals($coverage->getData(), $result->getData());
        $this->assertEquals($coverage->getTests(), $result->getTests());
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

    public function testRestoresCacheDirectoryConfiguration(): void
    {
        $coverage = $this->getLineCoverageForBankAccount();
        $coverage->cacheStaticAnalysis('/some/dir');
        $target = TEST_FILES_PATH . 'tmp/serialized.php';

        (new Serializer)->serialize($target, $coverage);
        $result = (new Unserializer)->unserialize($target);

        $this->assertTrue($result->cachesStaticAnalysis());
    }

    public function testRestoresCheckForUnintentionallyCoveredCodeConfiguration(): void
    {
        $coverage = $this->getLineCoverageForBankAccount();
        $coverage->enableCheckForUnintentionallyCoveredCode();
        $target = TEST_FILES_PATH . 'tmp/serialized.php';

        (new Serializer)->serialize($target, $coverage);
        $result = (new Unserializer)->unserialize($target);

        $property = new ReflectionProperty($result, 'checkForUnintentionallyCoveredCode');

        $this->assertTrue($property->getValue($result));
    }

    public function testRestoresIncludeUncoveredFilesConfiguration(): void
    {
        $coverage = $this->getLineCoverageForBankAccount();
        $coverage->excludeUncoveredFiles();
        $target = TEST_FILES_PATH . 'tmp/serialized.php';

        (new Serializer)->serialize($target, $coverage);
        $result = (new Unserializer)->unserialize($target);

        $property = new ReflectionProperty($result, 'includeUncoveredFiles');

        $this->assertFalse($property->getValue($result));
    }

    public function testRestoresIgnoreDeprecatedCodeConfiguration(): void
    {
        $coverage = $this->getLineCoverageForBankAccount();
        $coverage->ignoreDeprecatedCode();
        $target = TEST_FILES_PATH . 'tmp/serialized.php';

        (new Serializer)->serialize($target, $coverage);
        $result = (new Unserializer)->unserialize($target);

        $property = new ReflectionProperty($result, 'ignoreDeprecatedCode');

        $this->assertTrue($property->getValue($result));
    }

    public function testRestoresUseAnnotationsForIgnoringCodeConfiguration(): void
    {
        $coverage = $this->getLineCoverageForBankAccount();
        $coverage->disableAnnotationsForIgnoringCode();
        $target = TEST_FILES_PATH . 'tmp/serialized.php';

        (new Serializer)->serialize($target, $coverage);
        $result = (new Unserializer)->unserialize($target);

        $property = new ReflectionProperty($result, 'useAnnotationsForIgnoringCode');

        $this->assertFalse($property->getValue($result));
    }

    public function testRestoresParentClassesExcludedFromUnintentionallyCoveredCodeCheck(): void
    {
        $coverage = $this->getLineCoverageForBankAccount();

        /** @phpstan-ignore argument.type */
        $coverage->excludeSubclassesOfThisClassFromUnintentionallyCoveredCodeCheck('SomeClass');

        $target = TEST_FILES_PATH . 'tmp/serialized.php';

        (new Serializer)->serialize($target, $coverage);
        $result = (new Unserializer)->unserialize($target);

        $property = new ReflectionProperty($result, 'parentClassesExcludedFromUnintentionallyCoveredCodeCheck');

        $this->assertContains('SomeClass', $property->getValue($result));
    }

    public function testReturnedCodeCoverageUsesNullDriver(): void
    {
        $coverage = $this->getLineCoverageForBankAccount();
        $target   = TEST_FILES_PATH . 'tmp/serialized.php';

        (new Serializer)->serialize($target, $coverage);
        $result = (new Unserializer)->unserialize($target);

        $property = new ReflectionProperty($result, 'driver');

        $this->assertInstanceOf(NullDriver::class, $property->getValue($result));
    }
}
