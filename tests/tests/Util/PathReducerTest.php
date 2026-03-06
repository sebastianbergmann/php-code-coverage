<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Util;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData;

#[CoversClass(PathReducer::class)]
#[Small]
final class PathReducerTest extends TestCase
{
    public function testReduceWithNoFilesReturnsEmptyString(): void
    {
        $data = new ProcessedCodeCoverageData;

        $this->assertSame('', (new PathReducer)->reduce($data));
    }

    public function testReduceWithSingleAbsoluteFileReturnsDirnameAndRenamesFile(): void
    {
        $data = $this->dataWithFiles(['/path/to/file.php']);

        $result = (new PathReducer)->reduce($data);

        $this->assertSame('/path/to', $result);
        $this->assertSame(['file.php'], $data->coveredFiles());
    }

    public function testReduceWithSinglePharFileStripsSchemeAndReturnsDirname(): void
    {
        $data = $this->dataWithFiles(['phar:///path/to/file.php']);

        $result = (new PathReducer)->reduce($data);

        $this->assertSame('/path/to', $result);
        $this->assertSame(['file.php'], $data->coveredFiles());
    }

    public function testReduceWithMultipleFilesExtractsLongestCommonPath(): void
    {
        $data = $this->dataWithFiles([
            '/common/path/a.php',
            '/common/path/b.php',
        ]);

        $result = (new PathReducer)->reduce($data);

        $this->assertSame('/common/path', $result);
        $this->assertSame(['a.php', 'b.php'], $data->coveredFiles());
    }

    public function testReduceWithMultiplePharFilesStripsSchemeAndExtractsCommonPath(): void
    {
        $data = $this->dataWithFiles([
            'phar:///common/a.php',
            'phar:///common/b.php',
        ]);

        $result = (new PathReducer)->reduce($data);

        $this->assertSame('/common', $result);
        $this->assertSame(['a.php', 'b.php'], $data->coveredFiles());
    }

    public function testReduceWithMultipleFilesInDifferentDirectoriesReturnsRootPath(): void
    {
        $data = $this->dataWithFiles([
            '/alpha/x.php',
            '/beta/y.php',
        ]);

        $result = (new PathReducer)->reduce($data);

        $this->assertSame('', $result);
        $this->assertSame(['alpha/x.php', 'beta/y.php'], $data->coveredFiles());
    }

    /**
     * @param list<string> $files
     */
    private function dataWithFiles(array $files): ProcessedCodeCoverageData
    {
        $lineCoverage = [];

        foreach ($files as $file) {
            $lineCoverage[$file] = [];
        }

        $data = new ProcessedCodeCoverageData;

        $data->setLineCoverage($lineCoverage);

        return $data;
    }
}
