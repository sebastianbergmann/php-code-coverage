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

use const PHP_EOL;
use function array_replace_recursive;
use function file_put_contents;
use function serialize;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData;
use SebastianBergmann\CodeCoverage\TestCase;
use SebastianBergmann\CodeCoverage\Version;

#[CoversClass(Merger::class)]
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

    public function testMergedResultPreservesBasePathFromFirstItem(): void
    {
        $pathA = $this->writeFile('a.php', $this->makeItem(['basePath' => '/home/user/project']));
        $pathB = $this->writeFile('b.php', $this->makeItem(['basePath' => '/home/user/project']));

        $result = (new Merger)->merge([$pathA, $pathB]);

        $this->assertSame('/home/user/project', $result['basePath']);
    }

    public function testMergedResultHasFreshTimestamp(): void
    {
        $item = $this->makeItem(['buildInformation' => ['timestamp' => 'old timestamp']]);
        $path = $this->writeFile('a.php', $item);

        $result = (new Merger)->merge([$path]);

        $this->assertNotSame('old timestamp', $result['buildInformation']['timestamp']);
        $this->assertNotEmpty($result['buildInformation']['timestamp']);
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
        $coverageA->setLineCoverage(['/src/Foo.php' => [1 => ['test1'], 2 => null]]);

        $coverageB = new ProcessedCodeCoverageData;
        $coverageB->setLineCoverage(['/src/Foo.php' => [1 => [], 2 => ['test2']]]);

        $pathA = $this->writeFile('a.php', $this->makeItem([], $coverageA));
        $pathB = $this->writeFile('b.php', $this->makeItem([], $coverageB));

        $result = (new Merger)->merge([$pathA, $pathB]);

        $merged = $result['codeCoverage']->lineCoverage();
        $this->assertSame(['test1'], $merged['/src/Foo.php'][1]);
        $this->assertSame(['test2'], $merged['/src/Foo.php'][2]);
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
        $this->expectExceptionMessage('field "commit"');
        $this->expectExceptionMessage('"aaaaaaa"');
        $this->expectExceptionMessage('"bbbbbbb"');

        (new Merger)->merge([$pathA, $pathB]);
    }

    public function testThrowsExceptionWhenGitBranchDiffers(): void
    {
        $pathA = $this->writeFile('a.php', $this->makeItem(['buildInformation' => ['git' => $this->makeGit(['branch' => 'main'])]]));
        $pathB = $this->writeFile('b.php', $this->makeItem(['buildInformation' => ['git' => $this->makeGit(['branch' => 'feature'])]]));

        $this->expectException(GitInformationMismatchException::class);
        $this->expectExceptionMessage('field "branch"');

        (new Merger)->merge([$pathA, $pathB]);
    }

    public function testThrowsExceptionWhenGitOriginUrlDiffers(): void
    {
        $pathA = $this->writeFile('a.php', $this->makeItem(['buildInformation' => ['git' => $this->makeGit(['originUrl' => 'https://github.com/a/repo'])]]));
        $pathB = $this->writeFile('b.php', $this->makeItem(['buildInformation' => ['git' => $this->makeGit(['originUrl' => 'https://github.com/b/repo'])]]));

        $this->expectException(GitInformationMismatchException::class);
        $this->expectExceptionMessage('field "originUrl"');

        (new Merger)->merge([$pathA, $pathB]);
    }

    public function testThrowsExceptionWhenGitStatusDiffers(): void
    {
        $pathA = $this->writeFile('a.php', $this->makeItem(['buildInformation' => ['git' => $this->makeGit(['status' => ''])]]));
        $pathB = $this->writeFile('b.php', $this->makeItem(['buildInformation' => ['git' => $this->makeGit(['status' => 'M src/Foo.php'])]]));

        $this->expectException(GitInformationMismatchException::class);
        $this->expectExceptionMessage('field "status"');

        (new Merger)->merge([$pathA, $pathB]);
    }

    public function testThrowsExceptionWhenGitIsCleanDiffers(): void
    {
        $pathA = $this->writeFile('a.php', $this->makeItem(['buildInformation' => ['git' => $this->makeGit(['isClean' => true])]]));
        $pathB = $this->writeFile('b.php', $this->makeItem(['buildInformation' => ['git' => $this->makeGit(['isClean' => false])]]));

        $this->expectException(GitInformationMismatchException::class);
        $this->expectExceptionMessage('field "isClean"');
        $this->expectExceptionMessage('"true"');
        $this->expectExceptionMessage('"false"');

        (new Merger)->merge([$pathA, $pathB]);
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
                    'version'           => Version::id(),
                    'driverInformation' => [
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
     */
    private function writeFile(string $filename, array $data): string
    {
        $path = TEST_FILES_PATH . 'tmp/' . $filename;

        $content = '<?php // phpunit/php-code-coverage version ' . Version::id() . PHP_EOL .
            "return \unserialize(<<<'END_OF_COVERAGE_SERIALIZATION'" . PHP_EOL .
            serialize($data) . PHP_EOL .
            'END_OF_COVERAGE_SERIALIZATION' . PHP_EOL .
            ');';

        file_put_contents($path, $content);

        return $path;
    }
}
