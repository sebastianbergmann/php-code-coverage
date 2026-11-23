<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Serialization;

use const DIRECTORY_SEPARATOR;
use const PHP_EOL;
use function array_replace_recursive;
use function file_put_contents;
use function implode;
use function serialize;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Ticket;
use SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData;
use SebastianBergmann\CodeCoverage\TestCase;
use SebastianBergmann\CodeCoverage\Version;

#[CoversClass(Merger::class)]
#[CoversClass(BasePathMismatchException::class)]
#[CoversClass(DriverMismatchException::class)]
#[CoversClass(EmptyPathListException::class)]
#[CoversClass(GitInformationMismatchException::class)]
#[CoversClass(MixedGitInformationException::class)]
#[CoversClass(RuntimeMismatchException::class)]
#[Small]
final class MergerTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->removeTemporaryFiles();
    }

    public function testThrowsExceptionWhenPathListIsEmpty(): void
    {
        $this->expectException(EmptyPathListException::class);

        (new Merger)->merge([]);
    }

    public function testMergesSingleFile(): void
    {
        $path = $this->writeFile('a.php', $this->makeItem());

        $result = (new Merger)->merge([$path]);

        $this->assertArrayHasKey('buildInformation', $result);
        $this->assertArrayHasKey('basePath', $result);
        $this->assertArrayHasKey('codeCoverage', $result);
        $this->assertArrayHasKey('testResults', $result);
        $this->assertInstanceOf(ProcessedCodeCoverageData::class, $result['codeCoverage']);
    }

    public function testMergeWithGenerator(): void
    {
        $result = (new Merger)->merge($this->generatePathList());

        $this->assertArrayHasKey('buildInformation', $result);
        $this->assertArrayHasKey('basePath', $result);
        $this->assertArrayHasKey('codeCoverage', $result);
        $this->assertArrayHasKey('testResults', $result);
        $this->assertInstanceOf(ProcessedCodeCoverageData::class, $result['codeCoverage']);
    }

    public function testMergedResultPreservesBasePathFromFirstItem(): void
    {
        $pathA = $this->writeFile('a.php', $this->makeItem(['basePath' => '/home/user/project']));
        $pathB = $this->writeFile('b.php', $this->makeItem(['basePath' => '/home/user/project']));

        $result = (new Merger)->merge([$pathA, $pathB]);

        $this->assertSame('/home/user/project', $result['basePath']);
    }

    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/1408')]
    public function testPathsAreMovedOntoBasePathOfMergedDataWhenBasePathOfFileIsBelowIt(): void
    {
        $pathA = $this->writeFile(
            'a.php',
            $this->makeItem(
                ['basePath' => $this->path('project', 'src')],
                $this->coverage('a', [$this->relativePath('Alpha', 'One.php') => 8, $this->relativePath('Beta', 'Two.php') => 8]),
            ),
        );

        $pathB = $this->writeFile(
            'b.php',
            $this->makeItem(
                ['basePath' => $this->path('project', 'src', 'Alpha')],
                $this->coverage('b', ['One.php' => 13]),
            ),
        );

        $result = (new Merger)->merge([$pathA, $pathB]);

        $this->assertSame($this->path('project', 'src'), $result['basePath']);

        $this->assertSame(
            [
                $this->relativePath('Alpha', 'One.php') => [8 => [0 => 1], 13 => [1 => 1]],
                $this->relativePath('Beta', 'Two.php')  => [8 => [0 => 1]],
            ],
            $result['codeCoverage']->lineCoverage(),
        );
    }

    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/1408')]
    public function testPathsOfMergedDataAreMovedOntoBasePathOfFileWhenBasePathOfMergedDataIsBelowIt(): void
    {
        $pathA = $this->writeFile(
            'a.php',
            $this->makeItem(
                ['basePath' => $this->path('project', 'src', 'Alpha')],
                $this->coverage('a', ['One.php' => 13]),
            ),
        );

        $pathB = $this->writeFile(
            'b.php',
            $this->makeItem(
                ['basePath' => $this->path('project', 'src')],
                $this->coverage('b', [$this->relativePath('Alpha', 'One.php') => 8, $this->relativePath('Beta', 'Two.php') => 8]),
            ),
        );

        $result = (new Merger)->merge([$pathA, $pathB]);

        $this->assertSame($this->path('project', 'src'), $result['basePath']);

        $this->assertEquals(
            [
                $this->relativePath('Alpha', 'One.php') => [8 => [1 => 1], 13 => [0 => 1]],
                $this->relativePath('Beta', 'Two.php')  => [8 => [1 => 1]],
            ],
            $result['codeCoverage']->lineCoverage(),
        );
    }

    public function testFileIsNotConfusedWithFileOfSameNameWhenPathsAreMoved(): void
    {
        $pathA = $this->writeFile(
            'a.php',
            $this->makeItem(
                ['basePath' => $this->path('project', 'src')],
                $this->coverage('a', ['One.php' => 8]),
            ),
        );

        $pathB = $this->writeFile(
            'b.php',
            $this->makeItem(
                ['basePath' => $this->path('project', 'src', 'Alpha')],
                $this->coverage('b', ['One.php' => 13, $this->relativePath('Alpha', 'One.php') => 21, $this->relativePath('Alpha', 'Alpha', 'One.php') => 34]),
            ),
        );

        $result = (new Merger)->merge([$pathA, $pathB]);

        $this->assertSame($this->path('project', 'src'), $result['basePath']);

        $this->assertSame(
            [
                $this->relativePath('Alpha', 'Alpha', 'Alpha', 'One.php') => [34 => [1 => 1]],
                $this->relativePath('Alpha', 'Alpha', 'One.php')          => [21 => [1 => 1]],
                $this->relativePath('Alpha', 'One.php')                   => [13 => [1 => 1]],
                'One.php'                                                 => [8 => [0 => 1]],
            ],
            $result['codeCoverage']->lineCoverage(),
        );
    }

    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/1408')]
    public function testBasePathOfFileIsUsedWhenMergedDataHasNoCoveredFiles(): void
    {
        $pathA = $this->writeFile('a.php', $this->makeItem(['basePath' => '']));

        $pathB = $this->writeFile(
            'b.php',
            $this->makeItem(
                ['basePath' => $this->path('project', 'src')],
                $this->coverage('b', ['One.php' => 8]),
            ),
        );

        $result = (new Merger)->merge([$pathA, $pathB]);

        $this->assertSame($this->path('project', 'src'), $result['basePath']);
        $this->assertSame(['One.php'], $result['codeCoverage']->coveredFiles());
    }

    public function testBasePathOfMergedDataIsKeptWhenFileHasNoCoveredFiles(): void
    {
        $pathA = $this->writeFile(
            'a.php',
            $this->makeItem(
                ['basePath' => $this->path('project', 'src', 'Alpha')],
                $this->coverage('a', ['One.php' => 8]),
            ),
        );

        $pathB = $this->writeFile('b.php', $this->makeItem(['basePath' => '']));
        $pathC = $this->writeFile('c.php', $this->makeItem(['basePath' => $this->path('project', 'src')]));

        $result = (new Merger)->merge([$pathA, $pathB, $pathC]);

        $this->assertSame($this->path('project', 'src', 'Alpha'), $result['basePath']);
        $this->assertSame(['One.php'], $result['codeCoverage']->coveredFiles());
    }

    public function testPathsAreNotMovedWhenNeitherBasePathIsBelowTheOther(): void
    {
        $pathA = $this->writeFile(
            'a.php',
            $this->makeItem(
                ['basePath' => $this->path('first', 'project', 'src')],
                $this->coverage('a', ['One.php' => 8]),
            ),
        );

        $pathB = $this->writeFile(
            'b.php',
            $this->makeItem(
                ['basePath' => $this->path('second', 'project', 'src')],
                $this->coverage('b', ['One.php' => 13]),
            ),
        );

        $result = (new Merger)->merge([$pathA, $pathB]);

        $this->assertSame($this->path('first', 'project', 'src'), $result['basePath']);
        $this->assertSame(['One.php' => [8 => [0 => 1], 13 => [1 => 1]]], $result['codeCoverage']->lineCoverage());
    }

    public function testThrowsExceptionWhenNeitherBasePathIsBelowTheOtherAndMatchingBasePathsAreRequired(): void
    {
        $pathA = $this->writeFile(
            'a.php',
            $this->makeItem(
                ['basePath' => $this->path('first', 'project', 'src')],
                $this->coverage('a', ['One.php' => 8]),
            ),
        );

        $pathB = $this->writeFile(
            'b.php',
            $this->makeItem(
                ['basePath' => $this->path('second', 'project', 'src')],
                $this->coverage('b', ['One.php' => 13]),
            ),
        );

        $this->expectException(BasePathMismatchException::class);
        $this->expectExceptionMessageIsOrContains('Base path mismatch: "' . $this->path('first', 'project', 'src') . '" and "' . $this->path('second', 'project', 'src') . '" are not the same directory, and neither is a directory below the other');

        (new Merger)->merge([$pathA, $pathB], true, true, true, true);
    }

    public function testDoesNotThrowExceptionWhenOneBasePathIsBelowTheOtherAndMatchingBasePathsAreRequired(): void
    {
        $pathA = $this->writeFile(
            'a.php',
            $this->makeItem(
                ['basePath' => $this->path('project', 'src', 'Alpha')],
                $this->coverage('a', ['One.php' => 13]),
            ),
        );

        $pathB = $this->writeFile(
            'b.php',
            $this->makeItem(
                ['basePath' => $this->path('project', 'src')],
                $this->coverage('b', [$this->relativePath('Beta', 'Two.php') => 8]),
            ),
        );

        $pathC = $this->writeFile('c.php', $this->makeItem(['basePath' => '']));

        $result = (new Merger)->merge([$pathA, $pathB, $pathC], true, true, true, true);

        $this->assertSame($this->path('project', 'src'), $result['basePath']);
    }

    public function testPathsAreNotMovedWhenBasePathOnlyStartsWithTheOtherBasePath(): void
    {
        $pathA = $this->writeFile(
            'a.php',
            $this->makeItem(
                ['basePath' => $this->path('project', 'src')],
                $this->coverage('a', ['One.php' => 8]),
            ),
        );

        $pathB = $this->writeFile(
            'b.php',
            $this->makeItem(
                ['basePath' => $this->path('project', 'src-old')],
                $this->coverage('b', ['One.php' => 13]),
            ),
        );

        $pathC = $this->writeFile(
            'c.php',
            $this->makeItem(
                ['basePath' => $this->path('project', 'src') . DIRECTORY_SEPARATOR],
                $this->coverage('c', ['One.php' => 21]),
            ),
        );

        $result = (new Merger)->merge([$pathA, $pathB, $pathC]);

        $this->assertSame($this->path('project', 'src'), $result['basePath']);
        $this->assertSame(['One.php'], $result['codeCoverage']->coveredFiles());
    }

    public function testMergedResultHasFreshTimestamp(): void
    {
        $item = $this->makeItem(['buildInformation' => ['timestamp' => 'old timestamp']]);
        $path = $this->writeFile('a.php', $item);

        $result = (new Merger)->merge([$path]);

        $this->assertNotSame('old timestamp', $result['buildInformation']['timestamp']);
        $this->assertNotSame('', $result['buildInformation']['timestamp']);
    }

    public function testMergedResultUsesCurrentVersion(): void
    {
        $path = $this->writeFile('a.php', $this->makeItem());

        $result = (new Merger)->merge([$path]);

        $this->assertSame(Version::id(), $result['buildInformation']['phpCodeCoverage']['version']);
    }

    public function testMergesCodeCoverageData(): void
    {
        $coverageA = new ProcessedCodeCoverageData;
        $coverageA->setTestIds(['test1']);
        $coverageA->setLineCoverage(['/src/Foo.php' => [1 => [0 => 1], 2 => null]]);

        $coverageB = new ProcessedCodeCoverageData;
        $coverageB->setTestIds(['test2']);
        $coverageB->setLineCoverage(['/src/Foo.php' => [1 => [], 2 => [0 => 1]]]);

        $pathA = $this->writeFile('a.php', $this->makeItem([], $coverageA));
        $pathB = $this->writeFile('b.php', $this->makeItem([], $coverageB));

        $result = (new Merger)->merge([$pathA, $pathB]);

        $merged = $this->lineCoverageKeyedByTestId($result['codeCoverage']);

        $this->assertArrayHasKey('/src/Foo.php', $merged);

        $fooLines = $merged['/src/Foo.php'];

        $this->assertArrayHasKey(1, $fooLines);
        $this->assertArrayHasKey(2, $fooLines);
        $this->assertSame(['test1' => 1], $fooLines[1]);
        $this->assertSame(['test2' => 1], $fooLines[2]);
    }

    public function testMergesTestResults(): void
    {
        $itemA = $this->makeItem([], null, ['test1' => ['size' => 'small', 'status' => 'passed', 'time' => 0.1]]);
        $itemB = $this->makeItem([], null, ['test2' => ['size' => 'small', 'status' => 'passed', 'time' => 0.2]]);

        $pathA = $this->writeFile('a.php', $itemA);
        $pathB = $this->writeFile('b.php', $itemB);

        $result = (new Merger)->merge([$pathA, $pathB]);

        $this->assertArrayHasKey('test1', $result['testResults']);
        $this->assertArrayHasKey('test2', $result['testResults']);
    }

    public function testMergedResultHasNoGitKeyWhenNoFilesHaveGit(): void
    {
        $pathA = $this->writeFile('a.php', $this->makeItem());
        $pathB = $this->writeFile('b.php', $this->makeItem());

        $result = (new Merger)->merge([$pathA, $pathB]);

        $this->assertArrayNotHasKey('git', $result['buildInformation']);
    }

    public function testMergedResultPreservesGitInformationWhenAllFilesAgree(): void
    {
        $git  = $this->makeGit();
        $path = $this->writeFile('a.php', $this->makeItem(['buildInformation' => ['git' => $git]]));

        $result = (new Merger)->merge([$path]);

        $this->assertArrayHasKey('git', $result['buildInformation']);
        $this->assertSame($git, $result['buildInformation']['git']);
    }

    public function testThrowsExceptionWhenRuntimeNameDiffers(): void
    {
        $pathA = $this->writeFile('a.php', $this->makeItem(['buildInformation' => ['runtime' => ['name' => 'PHP']]]));
        $pathB = $this->writeFile('b.php', $this->makeItem(['buildInformation' => ['runtime' => ['name' => 'HHVM']]]));

        $this->expectException(RuntimeMismatchException::class);

        (new Merger)->merge([$pathA, $pathB]);
    }

    public function testThrowsExceptionWhenRuntimeVersionDiffers(): void
    {
        $pathA = $this->writeFile('a.php', $this->makeItem(['buildInformation' => ['runtime' => ['version' => '8.3.0']]]));
        $pathB = $this->writeFile('b.php', $this->makeItem(['buildInformation' => ['runtime' => ['version' => '8.2.0']]]));

        $this->expectException(RuntimeMismatchException::class);

        (new Merger)->merge([$pathA, $pathB]);
    }

    public function testThrowsExceptionWhenDriverNameDiffers(): void
    {
        $pathA = $this->writeFile('a.php', $this->makeItem(['buildInformation' => ['phpCodeCoverage' => ['driverInformation' => ['name' => 'Xdebug']]]]));
        $pathB = $this->writeFile('b.php', $this->makeItem(['buildInformation' => ['phpCodeCoverage' => ['driverInformation' => ['name' => 'PCOV']]]]));

        $this->expectException(DriverMismatchException::class);

        (new Merger)->merge([$pathA, $pathB]);
    }

    public function testThrowsExceptionWhenDriverVersionDiffers(): void
    {
        $pathA = $this->writeFile('a.php', $this->makeItem(['buildInformation' => ['phpCodeCoverage' => ['driverInformation' => ['version' => '3.1.0']]]]));
        $pathB = $this->writeFile('b.php', $this->makeItem(['buildInformation' => ['phpCodeCoverage' => ['driverInformation' => ['version' => '3.2.0']]]]));

        $this->expectException(DriverMismatchException::class);

        (new Merger)->merge([$pathA, $pathB]);
    }

    public function testThrowsExceptionWhenSomeFilesHaveGitAndOthersDont(): void
    {
        $pathA = $this->writeFile('a.php', $this->makeItem(['buildInformation' => ['git' => $this->makeGit()]]));
        $pathB = $this->writeFile('b.php', $this->makeItem());

        $this->expectException(MixedGitInformationException::class);

        (new Merger)->merge([$pathA, $pathB]);
    }

    public function testThrowsExceptionWhenGitCommitDiffers(): void
    {
        $pathA = $this->writeFile('a.php', $this->makeItem(['buildInformation' => ['git' => $this->makeGit(['commit' => 'aaaaaaa'])]]));
        $pathB = $this->writeFile('b.php', $this->makeItem(['buildInformation' => ['git' => $this->makeGit(['commit' => 'bbbbbbb'])]]));

        $this->expectException(GitInformationMismatchException::class);
        $this->expectExceptionMessageIs('Git information mismatch: field "commit" is "aaaaaaa" in the first file but "bbbbbbb" in another file');

        (new Merger)->merge([$pathA, $pathB]);
    }

    public function testThrowsExceptionWhenGitBranchDiffers(): void
    {
        $pathA = $this->writeFile('a.php', $this->makeItem(['buildInformation' => ['git' => $this->makeGit(['branch' => 'main'])]]));
        $pathB = $this->writeFile('b.php', $this->makeItem(['buildInformation' => ['git' => $this->makeGit(['branch' => 'feature'])]]));

        $this->expectException(GitInformationMismatchException::class);
        $this->expectExceptionMessageIs('Git information mismatch: field "branch" is "main" in the first file but "feature" in another file');

        (new Merger)->merge([$pathA, $pathB]);
    }

    public function testThrowsExceptionWhenGitOriginUrlDiffers(): void
    {
        $pathA = $this->writeFile('a.php', $this->makeItem(['buildInformation' => ['git' => $this->makeGit(['originUrl' => 'https://github.com/a/repo'])]]));
        $pathB = $this->writeFile('b.php', $this->makeItem(['buildInformation' => ['git' => $this->makeGit(['originUrl' => 'https://github.com/b/repo'])]]));

        $this->expectException(GitInformationMismatchException::class);
        $this->expectExceptionMessageIs('Git information mismatch: field "originUrl" is "https://github.com/a/repo" in the first file but "https://github.com/b/repo" in another file');

        (new Merger)->merge([$pathA, $pathB]);
    }

    public function testThrowsExceptionWhenGitStatusDiffers(): void
    {
        $pathA = $this->writeFile('a.php', $this->makeItem(['buildInformation' => ['git' => $this->makeGit(['status' => ''])]]));
        $pathB = $this->writeFile('b.php', $this->makeItem(['buildInformation' => ['git' => $this->makeGit(['status' => 'M src/Foo.php'])]]));

        $this->expectException(GitInformationMismatchException::class);
        $this->expectExceptionMessageIs('Git information mismatch: field "status" is "" in the first file but "M src/Foo.php" in another file');

        (new Merger)->merge([$pathA, $pathB]);
    }

    public function testThrowsExceptionWhenGitIsCleanDiffers(): void
    {
        $pathA = $this->writeFile('a.php', $this->makeItem(['buildInformation' => ['git' => $this->makeGit(['isClean' => true])]]));
        $pathB = $this->writeFile('b.php', $this->makeItem(['buildInformation' => ['git' => $this->makeGit(['isClean' => false])]]));

        $this->expectException(GitInformationMismatchException::class);
        $this->expectExceptionMessageIs('Git information mismatch: field "isClean" is "true" in the first file but "false" in another file');

        (new Merger)->merge([$pathA, $pathB]);
    }

    public function testDoesNotThrowExceptionWhenRuntimeDiffersAndCheckIsDisabled(): void
    {
        $pathA = $this->writeFile('a.php', $this->makeItem(['buildInformation' => ['runtime' => ['name' => 'PHP', 'version' => '8.3.0']]]));
        $pathB = $this->writeFile('b.php', $this->makeItem(['buildInformation' => ['runtime' => ['name' => 'HHVM', 'version' => '8.2.0']]]));

        $result = (new Merger)->merge([$pathA, $pathB], true, false);

        $this->assertArrayHasKey('codeCoverage', $result);
    }

    public function testDoesNotThrowExceptionWhenDriverDiffersAndCheckIsDisabled(): void
    {
        $pathA = $this->writeFile('a.php', $this->makeItem(['buildInformation' => ['phpCodeCoverage' => ['driverInformation' => ['name' => 'Xdebug', 'version' => '3.1.0']]]]));
        $pathB = $this->writeFile('b.php', $this->makeItem(['buildInformation' => ['phpCodeCoverage' => ['driverInformation' => ['name' => 'PCOV', 'version' => '3.2.0']]]]));

        $result = (new Merger)->merge([$pathA, $pathB], true, true, false);

        $this->assertArrayHasKey('codeCoverage', $result);
    }

    public function testDoesNotThrowExceptionWhenGitInformationDiffersAndCheckIsDisabled(): void
    {
        $pathA = $this->writeFile('a.php', $this->makeItem(['buildInformation' => ['git' => $this->makeGit(['commit' => 'aaaaaaa'])]]));
        $pathB = $this->writeFile('b.php', $this->makeItem(['buildInformation' => ['git' => $this->makeGit(['commit' => 'bbbbbbb'])]]));

        $result = (new Merger)->merge([$pathA, $pathB], false);

        $this->assertArrayHasKey('codeCoverage', $result);
    }

    public function testDoesNotThrowExceptionWhenMixedGitInformationAndCheckIsDisabled(): void
    {
        $pathA = $this->writeFile('a.php', $this->makeItem(['buildInformation' => ['git' => $this->makeGit()]]));
        $pathB = $this->writeFile('b.php', $this->makeItem());

        $result = (new Merger)->merge([$pathA, $pathB], false);

        $this->assertArrayHasKey('codeCoverage', $result);
    }

    /**
     * @return Generator<non-empty-string>
     */
    private function generatePathList(): Generator
    {
        yield $this->writeFile('a.php', $this->makeItem(['basePath' => '/home/user/project']));

        yield $this->writeFile('b.php', $this->makeItem(['basePath' => '/home/user/project']));
    }

    /**
     * @return non-empty-string
     */
    private function path(string ...$segments): string
    {
        return DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segments);
    }

    /**
     * @param non-empty-string $segment
     *
     * @return non-empty-string
     */
    private function relativePath(string $segment, string ...$segments): string
    {
        return implode(DIRECTORY_SEPARATOR, [$segment, ...$segments]);
    }

    /**
     * @param non-empty-string                      $testId
     * @param array<non-empty-string, positive-int> $executedLines
     */
    private function coverage(string $testId, array $executedLines): ProcessedCodeCoverageData
    {
        $lineCoverage = [];

        foreach ($executedLines as $file => $line) {
            $lineCoverage[$file] = [$line => [0 => 1]];
        }

        $coverage = new ProcessedCodeCoverageData;

        $coverage->setTestIds([$testId]);
        $coverage->setLineCoverage($lineCoverage);

        return $coverage;
    }

    /**
     * @param array<mixed>                                                    $overrides
     * @param array<string, array{size: string, status: string, time: float}> $testResults
     *
     * @return array<mixed>
     */
    private function makeItem(array $overrides = [], ?ProcessedCodeCoverageData $coverage = null, array $testResults = []): array
    {
        $item = [
            'buildInformation' => [
                'timestamp' => 'Thu Jan  1 0:00:00 UTC 2025',
                'runtime'   => [
                    'name'      => 'PHP',
                    'version'   => '8.3.0',
                    'vendorUrl' => 'https://php.net',
                ],
                'phpCodeCoverage' => [
                    'version'             => Version::id(),
                    'serializationFormat' => Serializer::SERIALIZATION_FORMAT,
                    'driverInformation'   => [
                        'name'    => 'Xdebug',
                        'version' => '3.3.0',
                    ],
                ],
            ],
            'basePath'     => '',
            'codeCoverage' => $coverage ?? new ProcessedCodeCoverageData,
            'testResults'  => $testResults,
        ];

        return array_replace_recursive($item, $overrides);
    }

    /**
     * @param array<mixed> $overrides
     *
     * @return array<mixed>
     */
    private function makeGit(array $overrides = []): array
    {
        return array_replace_recursive(
            [
                'originUrl' => 'https://github.com/sebastianbergmann/php-code-coverage',
                'branch'    => 'main',
                'commit'    => 'abc1234',
                'isClean'   => true,
                'status'    => '',
            ],
            $overrides,
        );
    }

    /**
     * @param array<mixed> $data
     *
     * @return non-empty-string
     */
    private function writeFile(string $filename, array $data): string
    {
        $path = TEST_FILES_PATH . 'tmp/' . $filename;

        $content = '<?php // phpunit/php-code-coverage serialization format ' . Serializer::SERIALIZATION_FORMAT . PHP_EOL .
            "return \unserialize(<<<'END_OF_COVERAGE_SERIALIZATION'" . PHP_EOL .
            serialize($data) . PHP_EOL .
            'END_OF_COVERAGE_SERIALIZATION' . PHP_EOL .
            ');';

        file_put_contents($path, $content);

        return $path;
    }
}
