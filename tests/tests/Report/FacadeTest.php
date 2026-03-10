<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report;

use const DIRECTORY_SEPARATOR;
use const PHP_EOL;
use function file_get_contents;
use function str_replace;
use PHPUnit\Framework\Attributes\CoversClass;
use SebastianBergmann\CodeCoverage\Serialization\Serializer;
use SebastianBergmann\CodeCoverage\Serialization\Unserializer;
use SebastianBergmann\CodeCoverage\TestCase;

#[CoversClass(Facade::class)]
final class FacadeTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->removeTemporaryFiles();
    }

    public function testCanBeCreatedFromCodeCoverageObject(): void
    {
        $facade = Facade::fromObject($this->getLineCoverageForBankAccount());

        $this->assertInstanceOf(Facade::class, $facade);
    }

    public function testCanBeCreatedFromSerializedData(): void
    {
        $serializedFile = TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . 'FacadeTest_serialized.php';

        (new Serializer)->serialize($serializedFile, $this->getLineCoverageForBankAccount());

        $serializedData = (new Unserializer)->unserialize($serializedFile);

        $facade = Facade::fromSerializedData($serializedData);

        $this->assertInstanceOf(Facade::class, $facade);
    }

    public function testFromSerializedDataCanRenderText(): void
    {
        $serializedFile = TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . 'FacadeTest_serialized.php';

        (new Serializer)->serialize($serializedFile, $this->getLineCoverageForBankAccount());

        $serializedData = (new Unserializer)->unserialize($serializedFile);

        $facade = Facade::fromSerializedData($serializedData);
        $result = $facade->renderText(null);

        $this->assertNotEmpty($result);
        $this->assertStringContainsString('Summary:', $result);
    }

    public function testRenderTextReturnsString(): void
    {
        $facade = Facade::fromObject($this->getLineCoverageForBankAccount());

        $result = $facade->renderText(null);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Text/BankAccount-line.txt',
            str_replace(PHP_EOL, "\n", $result),
        );
    }

    public function testRenderTextWritesToFile(): void
    {
        $facade = Facade::fromObject($this->getLineCoverageForBankAccount());
        $target = TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . 'FacadeTest_text.txt';

        $result = $facade->renderText($target);

        $this->assertFileExists($target);
        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Text/BankAccount-line.txt',
            str_replace(PHP_EOL, "\n", file_get_contents($target)),
        );
    }

    public function testRenderClover(): void
    {
        $facade = Facade::fromObject($this->getLineCoverageForBankAccount());
        $target = TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . 'FacadeTest_clover.xml';

        $facade->renderClover($target, 'BankAccount');

        $this->assertFileExists($target);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Clover/BankAccount-line.xml',
            file_get_contents($target),
        );
    }

    public function testRenderOpenClover(): void
    {
        $facade = Facade::fromObject($this->getLineCoverageForBankAccount());
        $target = TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . 'FacadeTest_openclover.xml';

        $facade->renderOpenClover($target, 'BankAccount');

        $this->assertFileExists($target);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/OpenClover/BankAccount-line.xml',
            file_get_contents($target),
        );
    }

    public function testRenderCobertura(): void
    {
        $facade = Facade::fromObject($this->getLineCoverageForBankAccount());
        $target = TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . 'FacadeTest_cobertura.xml';

        $facade->renderCobertura($target);

        $this->assertFileExists($target);
        $this->assertStringContainsString('<?xml', file_get_contents($target));
        $this->assertStringContainsString('<coverage', file_get_contents($target));
    }

    public function testRenderCrap4j(): void
    {
        $facade = Facade::fromObject($this->getLineCoverageForBankAccount());
        $target = TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . 'FacadeTest_crap4j.xml';

        $facade->renderCrap4j($target);

        $this->assertFileExists($target);
        $this->assertStringContainsString('<?xml', file_get_contents($target));
        $this->assertStringContainsString('crap_result', file_get_contents($target));
    }

    public function testRenderHtml(): void
    {
        $facade = Facade::fromObject($this->getLineCoverageForBankAccount());
        $target = TEST_FILES_PATH . 'tmp';

        $facade->renderHtml($target);

        $this->assertFileExists($target . DIRECTORY_SEPARATOR . 'index.html');
        $this->assertDirectoryExists($target);
    }

    public function testRenderXml(): void
    {
        $facade = Facade::fromObject($this->getLineCoverageForBankAccount());
        $target = TEST_FILES_PATH . 'tmp';

        $facade->renderXml($target);

        $this->assertFileExists($target . DIRECTORY_SEPARATOR . 'index.xml');
        $this->assertDirectoryExists($target);
    }
}
