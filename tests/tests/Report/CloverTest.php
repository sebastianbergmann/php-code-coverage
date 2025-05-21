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

use const PHP_EOL;
use function libxml_clear_errors;
use function libxml_get_errors;
use function libxml_use_internal_errors;
use function sprintf;
use function trim;
use DOMDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use SebastianBergmann\CodeCoverage\TestCase;

#[CoversClass(Clover::class)]
final class CloverTest extends TestCase
{
    public function testLineCoverageForBankAccountTest(): void
    {
        $clover = new Clover;

        $this->validateAndAssert(
            TEST_FILES_PATH . 'BankAccount-clover-line.xml',
            $clover->process($this->getLineCoverageForBankAccount(), null, 'BankAccount'),
        );
    }

    public function testPathCoverageForBankAccountTest(): void
    {
        $clover = new Clover;

        $this->validateAndAssert(
            TEST_FILES_PATH . 'BankAccount-clover-path.xml',
            $clover->process($this->getPathCoverageForBankAccount(), null, 'BankAccount'),
        );
    }

    public function testCloverForFileWithIgnoredLines(): void
    {
        $clover = new Clover;

        $this->validateAndAssert(
            TEST_FILES_PATH . 'ignored-lines-clover.xml',
            $clover->process($this->getCoverageForFileWithIgnoredLines()),
        );
    }

    public function testCloverForClassWithAnonymousFunction(): void
    {
        $clover = new Clover;

        $this->validateAndAssert(
            TEST_FILES_PATH . 'class-with-anonymous-function-clover.xml',
            $clover->process($this->getCoverageForClassWithAnonymousFunction()),
        );
    }

    /**
     * @param non-empty-string $expectationFile
     * @param non-empty-string $cloverXml
     */
    private function validateAndAssert(string $expectationFile, string $cloverXml): void
    {
        $this->assertStringMatchesFormatFile($expectationFile, $cloverXml);

        libxml_use_internal_errors(true);

        $document = new DOMDocument;
        $document->loadXML($cloverXml);

        if (!$document->schemaValidate(__DIR__ . '/../../_files/clover.xsd')) {
            $buffer = 'Generated XML document does not validate against Clover schema:' . PHP_EOL . PHP_EOL;

            foreach (libxml_get_errors() as $error) {
                $buffer .= sprintf(
                    '- Line %d: %s' . PHP_EOL,
                    $error->line,
                    trim($error->message),
                );
            }

            $buffer .= PHP_EOL;
        }

        libxml_clear_errors();

        if (isset($buffer)) {
            $this->fail($buffer);
        }
    }
}
