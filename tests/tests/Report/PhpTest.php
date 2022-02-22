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

use SebastianBergmann\CodeCoverage\TestCase;

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
        (new PHP)->process($coverage, self::$TEST_TMP_PATH . '/serialized.php');

        $unserialized = require self::$TEST_TMP_PATH . '/serialized.php';

        $this->assertEquals($coverage, $unserialized);
    }

    public function testPHPSerialisationProducesValidCodeWhenOutputIncludesSingleQuote(): void
    {
        $coverage = $this->getLineCoverageForFileWithEval();

        /* @noinspection UnusedFunctionResultInspection */
        (new PHP)->process($coverage, self::$TEST_TMP_PATH . '/serialized.php');

        $unserialized = require self::$TEST_TMP_PATH . '/serialized.php';

        $this->assertEquals($coverage, $unserialized);
    }
}
