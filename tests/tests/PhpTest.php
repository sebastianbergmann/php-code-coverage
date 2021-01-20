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

    public function testMemoryUsage(): void
    {
        // Approx 75MB when serialized
        $coverage   = $this->getLineCoverageForBankAccountByPoints(100000);
        $serialized = serialize($coverage);
        $size       = strlen($serialized);

        // Using memory_get_peak_usage() to determine if memory has been optimised so approximately
        // filling memory up to current measured peak usage allowing us to get a more accurate peak
        // usage value when measuring process() memory usage
        $memFill = str_repeat('a', memory_get_peak_usage(true) - memory_get_usage(true));

        $before = memory_get_peak_usage(true);
        /* @noinspection UnusedFunctionResultInspection */
        (new PHP)->process($coverage, self::$TEST_TMP_PATH . '/serialized.php');
        $after = memory_get_peak_usage(true);

        // Max memory used should be up to 3 x serialized size (however doesn't scale when string increases in size)
        $this->assertLessThan($size * 3, $after - $before);
        // Temporary usage of fill
        $this->assertNotNull($memFill);
    }
}
