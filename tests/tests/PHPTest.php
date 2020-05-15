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

class PHPTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->removeTemporaryFiles();
    }

    public function testPHPSerialisationProducesValidCode(): void
    {
        $coverage = $this->getCoverageForBankAccount();

        (new PHP())->process($coverage, self::$TEST_TMP_PATH . '/serialised.php');

        $unserialised = require self::$TEST_TMP_PATH . '/serialised.php';

        $this->assertEquals($coverage, $unserialised);
    }
}
