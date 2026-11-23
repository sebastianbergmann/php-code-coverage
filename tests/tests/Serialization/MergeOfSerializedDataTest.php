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
use function realpath;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\Ticket;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Data\RawCodeCoverageData;
use SebastianBergmann\CodeCoverage\Driver\Driver;
use SebastianBergmann\CodeCoverage\FakeDriver;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\Report\Facade;
use SebastianBergmann\CodeCoverage\TestCase;

#[CoversClass(Merger::class)]
#[CoversClass(Serializer::class)]
#[Medium]
final class MergeOfSerializedDataTest extends TestCase
{
    private const string ALPHA = 'Alpha' . DIRECTORY_SEPARATOR . 'One.php';
    private const string BETA  = 'Beta' . DIRECTORY_SEPARATOR . 'One.php';

    /**
     * @var non-empty-string
     */
    private string $sourceDirectory;

    /**
     * Each element is the lines that one process executed, by file.
     *
     * @return non-empty-array<non-empty-string, array{0: list<array<non-empty-string, list<positive-int>>>}>
     */
    public static function processes(): array
    {
        return [
            'one file, then the same file and a file in another directory' => [
                [
                    [self::ALPHA => [13]],
                    [self::ALPHA => [8], self::BETA => [8]],
                ],
            ],
            'two files in different directories, then one of them' => [
                [
                    [self::ALPHA => [8], self::BETA => [8]],
                    [self::ALPHA => [13]],
                ],
            ],
            'files in different directories that have the same name' => [
                [
                    [self::ALPHA => [8, 13]],
                    [self::BETA => [8]],
                ],
            ],
            'no file, then two files in different directories' => [
                [
                    [],
                    [self::ALPHA => [8, 13], self::BETA => [8]],
                ],
            ],
        ];
    }

    protected function setUp(): void
    {
        $sourceDirectory = realpath(TEST_FILES_PATH . 'merge-with-different-base-paths' . DIRECTORY_SEPARATOR . 'src');

        $this->assertNotFalse($sourceDirectory);
        $this->assertNotSame('', $sourceDirectory);

        $this->sourceDirectory = $sourceDirectory;
    }

    protected function tearDown(): void
    {
        $this->removeTemporaryFiles();
    }

    /**
     * @param list<array<non-empty-string, list<positive-int>>> $processes
     */
    #[DataProvider('processes')]
    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/1408')]
    public function testDataIsNotLostWhenProcessesCoveredDifferentFiles(array $processes): void
    {
        $paths = [];

        foreach ($processes as $index => $executedLines) {
            $path = TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . 'process-' . $index . '.cov';

            (new Serializer)->serialize($path, $this->codeCoverageOfProcess('process-' . $index, $executedLines));

            $paths[] = $path;
        }

        $merged = (new Merger)->merge($paths);

        $this->assertSame($this->sourceDirectory, $merged['basePath']);
        $this->assertSame([self::ALPHA, self::BETA], $merged['codeCoverage']->coveredFiles());

        $summary = Facade::fromSerializedData($merged)->summary();

        $this->assertSame(3, $summary->numberOfExecutableLines());
        $this->assertSame(3, $summary->numberOfExecutedLines());
    }

    /**
     * @param non-empty-string                            $id
     * @param array<non-empty-string, list<positive-int>> $executedLines
     */
    private function codeCoverageOfProcess(string $id, array $executedLines): CodeCoverage
    {
        $lineCoverage = [];

        foreach ($executedLines as $file => $lines) {
            foreach ($lines as $line) {
                $lineCoverage[$this->sourceDirectory . DIRECTORY_SEPARATOR . $file][$line] = Driver::LINE_EXECUTED;
            }
        }

        $driver = new FakeDriver(RawCodeCoverageData::fromLineCoverage($lineCoverage));

        $filter = new Filter;

        $filter->includeFiles(
            [
                $this->sourceDirectory . DIRECTORY_SEPARATOR . self::ALPHA,
                $this->sourceDirectory . DIRECTORY_SEPARATOR . self::BETA,
            ],
        );

        $codeCoverage = new CodeCoverage($driver, $filter);

        $codeCoverage->excludeUncoveredFiles();
        $codeCoverage->start($id);
        $codeCoverage->stop();

        return $codeCoverage;
    }
}
