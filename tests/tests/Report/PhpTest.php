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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use ReflectionProperty;
use SebastianBergmann\CodeCoverage\TestCase;

#[CoversClass(PHP::class)]
#[Small]
final class PhpTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->removeTemporaryFiles();
    }

    public function testPHPSerialisationProducesValidCode(): void
    {
        $coverage = $this->getLineCoverageForBankAccount();

        /* @noinspection UnusedFunctionResultInspection */
        (new PHP)->process($coverage, TEST_FILES_PATH . 'tmp/serialized.php');

        $unserialized = require TEST_FILES_PATH . 'tmp/serialized.php';

        $this->assertEquals($coverage, $unserialized);
    }

    public function testPHPSerialisationProducesValidCodeWhenOutputIncludesSingleQuote(): void
    {
        $coverage = $this->getLineCoverageForFileWithEval();

        /* @noinspection UnusedFunctionResultInspection */
        (new PHP)->process($coverage, TEST_FILES_PATH . 'tmp/serialized.php');

        $unserialized = require TEST_FILES_PATH . 'tmp/serialized.php';

        $this->assertEquals($coverage, $unserialized);
    }

    public function testCacheDataNeverGetSaved(): void
    {
        $coverage = $this->getLineCoverageForBankAccount();

        // Warm up cache
        $coverage->getReport();

        $refProperty = new ReflectionProperty($coverage, 'cachedReport');

        $this->assertNotNull($refProperty->getValue($coverage));

        /* @noinspection UnusedFunctionResultInspection */
        (new PHP)->process($coverage, TEST_FILES_PATH . 'tmp/serialized.php');

        $this->assertNull($refProperty->getValue($coverage));
    }
}
