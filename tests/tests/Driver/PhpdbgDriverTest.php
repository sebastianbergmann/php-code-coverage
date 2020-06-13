<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Driver;

use SebastianBergmann\CodeCoverage\TestCase;

final class PhpdbgDriverTest extends TestCase
{
    protected function setUp(): void
    {
        if (\PHP_SAPI !== 'phpdbg') {
            $this->markTestSkipped('This test requires the PHPDBG commandline interpreter');
        }
    }
}
