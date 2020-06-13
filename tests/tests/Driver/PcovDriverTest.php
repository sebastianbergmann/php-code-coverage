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

final class PcovDriverTest extends TestCase
{
    protected function setUp(): void
    {
        if (\PHP_SAPI !== 'cli') {
            $this->markTestSkipped('This test requires the PHP commandline interpreter');
        }

        if (!\extension_loaded('pcov')) {
            $this->markTestSkipped('This test requires the PCOV extension to be loaded');
        }

        if (!\ini_get('pcov.enabled')) {
            $this->markTestSkipped('This test requires the PCOV extension to be enabled');
        }
    }
}
