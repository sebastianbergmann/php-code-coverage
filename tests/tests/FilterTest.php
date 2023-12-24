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
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

#[CoversClass(Filter::class)]
final class FilterTest extends TestCase
{
    private Filter $filter;

    protected function setUp(): void
    {
        $this->filter = new Filter;
    }

    public function testIsInitiallyEmpty(): void
    {
        $this->assertTrue($this->filter->isEmpty());
    }

    public function testSingleFileCanBeAdded(): void
    {
        $file = realpath(__DIR__ . '/../_files/filter/a.php');

        $this->filter->includeFile($file);

        $this->assertFalse($this->filter->isEmpty());

        $this->assertSame(
            [
                $file,
            ],
            $this->filter->files(),
        );
    }

    public function testMultipleFilesCanBeAdded(): void
    {
        $files = [
            realpath(__DIR__ . '/../_files/filter/a.php'),
            realpath(__DIR__ . '/../_files/filter/b.php'),
        ];

        $this->filter->includeFiles($files);

        $this->assertSame($files, $this->filter->files());
    }

    public function testDeterminesWhetherStringContainsNameOfRealFileThatExists(): void
    {
        $this->assertFalse($this->filter->isFile('vfs://root/a/path'));
        $this->assertFalse($this->filter->isFile('xdebug://debug-eval'));
        $this->assertFalse($this->filter->isFile('eval()\'d code'));
        $this->assertFalse($this->filter->isFile('runtime-created function'));
        $this->assertFalse($this->filter->isFile('assert code'));
        $this->assertFalse($this->filter->isFile('regexp code'));
        $this->assertTrue($this->filter->isFile(__DIR__ . '/../_files/filter/a.php'));
    }

    public function testIncludedFileIsNotFiltered(): void
    {
        $this->filter->includeFile(realpath(__DIR__ . '/../_files/filter/a.php'));

        $this->assertFalse($this->filter->isExcluded(realpath(__DIR__ . '/../_files/filter/a.php')));
    }

    public function testNotIncludedFileIsFiltered(): void
    {
        $this->filter->includeFile(realpath(__DIR__ . '/../_files/filter/a.php'));

        $this->assertTrue($this->filter->isExcluded(realpath(__DIR__ . '/../_files/filter/b.php')));
    }

    public function testNonFilesAreFiltered(): void
    {
        $this->assertTrue($this->filter->isExcluded('vfs://root/a/path'));
        $this->assertTrue($this->filter->isExcluded('xdebug://debug-eval'));
        $this->assertTrue($this->filter->isExcluded('eval()\'d code'));
        $this->assertTrue($this->filter->isExcluded('runtime-created function'));
        $this->assertTrue($this->filter->isExcluded('assert code'));
        $this->assertTrue($this->filter->isExcluded('regexp code'));
    }

    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/664')]
    public function testTryingToAddFileThatDoesNotExistDoesNotChangeFilter(): void
    {
        $this->filter->includeFile('does_not_exist');

        $this->assertTrue($this->filter->isEmpty());
        $this->assertSame([], $this->filter->files());
    }
}
