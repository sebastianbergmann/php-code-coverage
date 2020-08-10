<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\StaticAnalysis;

use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\TestCase;

/**
 * @covers \SebastianBergmann\CodeCoverage\StaticAnalysis\CacheWarmer
 */
final class CacheWarmerTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->removeTemporaryFiles();
    }

    public function testEveryCachebleMethodIsCalled(): void
    {
        $filter = new Filter();
        $filter->includeFiles([TEST_FILES_PATH . 'BankAccount.php']);

        (new CacheWarmer())->warmCache(self::$TEST_TMP_PATH, false, false, $filter);

        $expectedWarmedCacheFiles = count(get_class_methods(CoveredFileAnalyser::class)) + count(get_class_methods(UncoveredFileAnalyser::class));
        $actualWarmedCacheFiles   = count(glob(self::$TEST_TMP_PATH . DIRECTORY_SEPARATOR . '*'));

        $this->assertSame($actualWarmedCacheFiles, $expectedWarmedCacheFiles);
    }
}
