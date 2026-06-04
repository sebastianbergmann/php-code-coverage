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
use function array_shift;
use function explode;
use function is_array;
use function is_file;
use function sha1_file;
use function substr;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData;
use SebastianBergmann\CodeCoverage\StaticAnalysis\FileAnalyser;
use SebastianBergmann\CodeCoverage\Util\PathReducer;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @phpstan-import-type TestType from CodeCoverage
 */
final readonly class Builder
{
    private FileAnalyser $analyser;

    public function __construct(FileAnalyser $analyser)
    {
        $this->analyser = $analyser;
    }

    /**
     * @param array<non-empty-string, TestType> $testResults
     */
    public function build(ProcessedCodeCoverageData $codeCoverage, array $testResults, string $basePath = ''): Directory
    {
        // Clone because path munging is destructive to the original data
        $codeCoverage = clone $codeCoverage;

        $commonPath = (new PathReducer)->reduce($codeCoverage);

        if ($commonPath === '') {
            $commonPath = '.';
        }

        $rootPath = $commonPath;

        if ($basePath !== '') {
            if ($commonPath === '.') {
                $rootPath = $basePath;
            } else {
                $rootPath = $basePath . DIRECTORY_SEPARATOR . $commonPath;
            }
        }

        $root = new Directory($rootPath, null);

        $this->addItems(
            $root,
            $this->buildDirectoryStructure($codeCoverage),
            $testResults,
        );

        return $root;
    }

    /**
     * @param array<array-key, mixed>           $items
     * @param array<non-empty-string, TestType> $tests
     */
    private function addItems(Directory $root, array $items, array $tests): void
    {
        foreach ($items as $key => $value) {
            $key = (string) $key;

            if ($value instanceof FileCoverageData) {
                $key      = substr($key, 0, -2);
                $filename = $root->pathAsString() . DIRECTORY_SEPARATOR . $key;

                $sha1 = is_file($filename) ? sha1_file($filename) : false;

                if ($sha1 !== false) {
                    $analysisResult = $this->analyser->analyse($filename);

                    $root->addFile(
                        new File(
                            $key,
                            $root,
                            $sha1,
                            $value->lineCoverage,
                            $value->functionCoverage,
                            $tests,
                            $analysisResult->classes(),
                            $analysisResult->traits(),
                            $analysisResult->functions(),
                            $analysisResult->linesOfCode(),
                            $value->functionCoverage !== [],
                        ),
                    );
                }
            } elseif (is_array($value)) {
                $child = $root->addDirectory($key);

                $this->addItems($child, $value, $tests);
            }
        }
    }

    /**
     * Builds an array representation of the directory structure.
     *
     * For instance,
     *
     * <code>
     * Array
     * (
     *     [Money.php] => Array
     *         (
     *             ...
     *         )
     *
     *     [MoneyBag.php] => Array
     *         (
     *             ...
     *         )
     * )
     * </code>
     *
     * is transformed into
     *
     * <code>
     * Array
     * (
     *     [.] => Array
     *         (
     *             [Money.php] => Array
     *                 (
     *                     ...
     *                 )
     *
     *             [MoneyBag.php] => Array
     *                 (
     *                     ...
     *                 )
     *         )
     * )
     * </code>
     *
     * @return array<array-key, mixed>
     */
    private function buildDirectoryStructure(ProcessedCodeCoverageData $codeCoverage): array
    {
        $result = [];

        $lineCoverage     = $codeCoverage->lineCoverage();
        $functionCoverage = $codeCoverage->functionCoverage();

        foreach ($codeCoverage->coveredFiles() as $originalPath) {
            $result = $this->insertIntoDirectoryStructure(
                $result,
                explode(DIRECTORY_SEPARATOR, $originalPath),
                new FileCoverageData(
                    $lineCoverage[$originalPath] ?? [],
                    $functionCoverage[$originalPath] ?? [],
                ),
            );
        }

        return $result;
    }

    /**
     * @param array<array-key, mixed> $structure
     * @param list<string>            $path
     *
     * @return array<array-key, mixed>
     */
    private function insertIntoDirectoryStructure(array $structure, array $path, FileCoverageData $leaf): array
    {
        $segment = array_shift($path);

        if ($segment === null) {
            // @codeCoverageIgnoreStart
            return $structure;
            // @codeCoverageIgnoreEnd
        }

        if ($path === []) {
            $structure[$segment . '/f'] = $leaf;

            return $structure;
        }

        $child = $structure[$segment] ?? [];

        if (!is_array($child)) {
            // @codeCoverageIgnoreStart
            $child = [];
            // @codeCoverageIgnoreEnd
        }

        $structure[$segment] = $this->insertIntoDirectoryStructure($child, $path, $leaf);

        return $structure;
    }
}
