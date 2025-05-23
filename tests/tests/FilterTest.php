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

use function realpath;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

#[CoversClass(Filter::class)]
#[Small]
final class FilterTest extends TestCase
{
    public function testIsInitiallyEmpty(): void
    {
        $filter = new Filter;

        $this->assertTrue($filter->isEmpty());
    }

    public function testSingleFileCanBeAdded(): void
    {
        $filter = new Filter;

        $file = realpath(__DIR__ . '/../_files/filter/a.php');

        $filter->includeFile($file);

        $this->assertFalse($filter->isEmpty());

        $this->assertSame(
            [
                $file,
            ],
            $filter->files(),
        );
    }

    public function testMultipleFilesCanBeAdded(): void
    {
        $filter = new Filter;

        $files = [
            realpath(__DIR__ . '/../_files/filter/a.php'),
            realpath(__DIR__ . '/../_files/filter/b.php'),
        ];

        $filter->includeFiles($files);

        $this->assertSame($files, $filter->files());
    }

    public function testDeterminesWhetherStringContainsNameOfRealFileThatExists(): void
    {
        $filter = new Filter;

        $this->assertFalse($filter->isFile('vfs://root/a/path'));
        $this->assertFalse($filter->isFile('xdebug://debug-eval'));
        $this->assertFalse($filter->isFile('eval()\'d code'));
        $this->assertFalse($filter->isFile('runtime-created function'));
        $this->assertFalse($filter->isFile('assert code'));
        $this->assertFalse($filter->isFile('regexp code'));
        $this->assertTrue($filter->isFile(__DIR__ . '/../_files/filter/a.php'));
    }

    public function testIncludedFileIsNotFiltered(): void
    {
        $filter = new Filter;

        $filter->includeFile(realpath(__DIR__ . '/../_files/filter/a.php'));

        $this->assertFalse($filter->isExcluded(realpath(__DIR__ . '/../_files/filter/a.php')));

        /**
         * Assertion is performed twice to test the is-cached path.
         */
        $this->assertFalse($filter->isExcluded(realpath(__DIR__ . '/../_files/filter/a.php')));
    }

    public function testNotIncludedFileIsFiltered(): void
    {
        $filter = new Filter;

        $filter->includeFile(realpath(__DIR__ . '/../_files/filter/a.php'));

        $this->assertTrue($filter->isExcluded(realpath(__DIR__ . '/../_files/filter/b.php')));
    }

    public function testNonFilesAreFiltered(): void
    {
        $filter = new Filter;

        $this->assertTrue($filter->isExcluded('vfs://root/a/path'));
        $this->assertTrue($filter->isExcluded('xdebug://debug-eval'));
        $this->assertTrue($filter->isExcluded('eval()\'d code'));
        $this->assertTrue($filter->isExcluded('runtime-created function'));
        $this->assertTrue($filter->isExcluded('assert code'));
        $this->assertTrue($filter->isExcluded('regexp code'));
    }

    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/664')]
    public function testTryingToAddFileThatDoesNotExistDoesNotChangeFilter(): void
    {
        $filter = new Filter;

        $filter->includeFile('does_not_exist');

        $this->assertTrue($filter->isEmpty());
        $this->assertSame([], $filter->files());
    }
}
