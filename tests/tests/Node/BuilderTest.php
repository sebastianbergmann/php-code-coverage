<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Node;

use const DIRECTORY_SEPARATOR;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData;
use SebastianBergmann\CodeCoverage\StaticAnalysis\FileAnalyser;
use SebastianBergmann\CodeCoverage\StaticAnalysis\ParsingSourceAnalyser;
use SebastianBergmann\CodeCoverage\TestCase;

#[CoversClass(Builder::class)]
#[Medium]
final class BuilderTest extends TestCase
{
    public function testBuildsTreeOfNodesFromCoverageData(): void
    {
        $root = $this->builder()->build(
            $this->getLineCoverageForBankAccount()->getData(),
            [],
        );

        $this->assertSame('index', $root->id());
        $this->assertCount(1, $root->files());

        $file = $root->files()[0];

        $this->assertSame('BankAccount.php', $file->name());
    }

    public function testBuildsTreeForMultipleFilesInTheSameDirectory(): void
    {
        $root = $this->builder()->build(
            $this->getCoverageForFilesWithUncoveredIncluded()->getData(),
            [],
        );

        $this->assertCount(2, $root->files());
    }

    public function testPrependsBasePathToRootPath(): void
    {
        $basePath = DIRECTORY_SEPARATOR . 'does-not-exist';

        $root = $this->builder()->build(
            $this->getLineCoverageForBankAccount()->getData(),
            [],
            $basePath,
        );

        $this->assertStringStartsWith($basePath, $root->pathAsString());

        // The munged root path does not point at a real file, so no file node is added
        $this->assertCount(0, $root->files());
    }

    public function testUsesCurrentDirectoryAsRootPathWhenThereIsNoCommonPath(): void
    {
        $root = $this->builder()->build(new ProcessedCodeCoverageData, []);

        $this->assertSame('index', $root->id());
        $this->assertSame('.', $root->name());
        $this->assertCount(0, $root->files());
    }

    public function testCreatesNestedDirectoriesForFilesInDifferentSubdirectories(): void
    {
        $data = new ProcessedCodeCoverageData;

        $data->setLineCoverage(
            [
                DIRECTORY_SEPARATOR . 'project' . DIRECTORY_SEPARATOR . 'sub1' . DIRECTORY_SEPARATOR . 'A.php' => [1 => []],
                DIRECTORY_SEPARATOR . 'project' . DIRECTORY_SEPARATOR . 'sub2' . DIRECTORY_SEPARATOR . 'B.php' => [1 => []],
            ],
        );

        $root = $this->builder()->build($data, []);

        $this->assertCount(2, $root->directories());
    }

    public function testUsesBasePathAsRootPathWhenCommonPathIsCurrentDirectory(): void
    {
        $data = new ProcessedCodeCoverageData;
        $data->setLineCoverage(['File.php' => [1 => []]]);

        $basePath = DIRECTORY_SEPARATOR . 'does-not-exist';

        $root = $this->builder()->build($data, [], $basePath);

        $this->assertSame($basePath, $root->pathAsString());
    }

    private function builder(): Builder
    {
        return new Builder(
            new FileAnalyser(
                new ParsingSourceAnalyser,
                false,
                false,
            ),
        );
    }
}
