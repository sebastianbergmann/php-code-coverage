<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report;

use const DIRECTORY_SEPARATOR;
use function rtrim;
use ReflectionMethod;
use SebastianBergmann\CodeCoverage\Node\Builder;
use SebastianBergmann\CodeCoverage\ProcessedCodeCoverageData;
use SebastianBergmann\CodeCoverage\TestCase;

final class BuilderTest extends TestCase
{
    private $factory;

    protected function setUp(): void
    {
        $this->factory = new Builder;
    }

    public function testSomething(): void
    {
        $root = $this->getLineCoverageForBankAccount()->getReport();

        $expectedPath = rtrim(TEST_FILES_PATH, DIRECTORY_SEPARATOR);
        $this->assertEquals($expectedPath, $root->name());
        $this->assertEquals($expectedPath, $root->pathAsString());
        $this->assertEquals(10, $root->numberOfExecutableLines());
        $this->assertEquals(5, $root->numberOfExecutedLines());
        $this->assertEquals(1, $root->numberOfClasses());
        $this->assertEquals(0, $root->numberOfTestedClasses());
        $this->assertEquals(4, $root->numberOfMethods());
        $this->assertEquals(3, $root->numberOfTestedMethods());
        $this->assertEquals('0.00%', $root->percentageOfTestedClasses()->asString());
        $this->assertEquals('75.00%', $root->percentageOfTestedMethods()->asString());
        $this->assertEquals('50.00%', $root->percentageOfExecutedLines()->asString());
        $this->assertEquals(0, $root->numberOfFunctions());
        $this->assertEquals(0, $root->numberOfTestedFunctions());
        $this->assertNull($root->parent());
        $this->assertEquals([], $root->directories());
        #$this->assertEquals(array(), $root->getFiles());
        #$this->assertEquals(array(), $root->getChildNodes());

        $this->assertEquals(
            [
                'BankAccount' => [
                    'methods' => [
                        'getBalance' => [
                            'signature'          => 'getBalance()',
                            'startLine'          => 6,
                            'endLine'            => 9,
                            'executableLines'    => 1,
                            'executedLines'      => 1,
                            'executableBranches' => 0,
                            'executedBranches'   => 0,
                            'executablePaths'    => 0,
                            'executedPaths'      => 0,
                            'ccn'                => 1,
                            'coverage'           => 100,
                            'crap'               => '1',
                            'link'               => 'BankAccount.php.html#6',
                            'methodName'         => 'getBalance',
                            'visibility'         => 'public',
                        ],
                        'setBalance' => [
                            'signature'          => 'setBalance($balance)',
                            'startLine'          => 11,
                            'endLine'            => 18,
                            'executableLines'    => 5,
                            'executedLines'      => 0,
                            'executableBranches' => 0,
                            'executedBranches'   => 0,
                            'executablePaths'    => 0,
                            'executedPaths'      => 0,
                            'ccn'                => 2,
                            'coverage'           => 0,
                            'crap'               => 6,
                            'link'               => 'BankAccount.php.html#11',
                            'methodName'         => 'setBalance',
                            'visibility'         => 'protected',
                        ],
                        'depositMoney' => [
                            'signature'          => 'depositMoney($balance)',
                            'startLine'          => 20,
                            'endLine'            => 25,
                            'executableLines'    => 2,
                            'executedLines'      => 2,
                            'executableBranches' => 0,
                            'executedBranches'   => 0,
                            'executablePaths'    => 0,
                            'executedPaths'      => 0,
                            'ccn'                => 1,
                            'coverage'           => 100,
                            'crap'               => '1',
                            'link'               => 'BankAccount.php.html#20',
                            'methodName'         => 'depositMoney',
                            'visibility'         => 'public',
                        ],
                        'withdrawMoney' => [
                            'signature'          => 'withdrawMoney($balance)',
                            'startLine'          => 27,
                            'endLine'            => 32,
                            'executableLines'    => 2,
                            'executedLines'      => 2,
                            'executableBranches' => 0,
                            'executedBranches'   => 0,
                            'executablePaths'    => 0,
                            'executedPaths'      => 0,
                            'ccn'                => 1,
                            'coverage'           => 100,
                            'crap'               => '1',
                            'link'               => 'BankAccount.php.html#27',
                            'methodName'         => 'withdrawMoney',
                            'visibility'         => 'public',
                        ],
                    ],
                    'startLine'          => 2,
                    'executableLines'    => 10,
                    'executedLines'      => 5,
                    'executableBranches' => 0,
                    'executedBranches'   => 0,
                    'executablePaths'    => 0,
                    'executedPaths'      => 0,
                    'ccn'                => 5,
                    'coverage'           => 50,
                    'crap'               => '8.12',
                    'package'            => [
                        'namespace'   => '',
                        'fullPackage' => '',
                        'category'    => '',
                        'package'     => '',
                        'subpackage'  => '',
                    ],
                    'link'      => 'BankAccount.php.html#2',
                    'className' => 'BankAccount',
                ],
            ],
            $root->classes()
        );

        $this->assertEquals([], $root->functions());
    }

    public function testBuildDirectoryStructure(): void
    {
        $s = DIRECTORY_SEPARATOR;

        $method = new ReflectionMethod(
            Builder::class,
            'buildDirectoryStructure'
        );

        $method->setAccessible(true);

        $this->assertEquals(
            [
                'src' => [
                    'Money.php/f'    => [
                        'lineCoverage'     => [],
                        'functionCoverage' => [],
                    ],
                    'MoneyBag.php/f' => [
                        'lineCoverage'     => [],
                        'functionCoverage' => [],
                    ],
                    'Foo'            => [
                        'Bar' => [
                            'Baz' => [
                                'Foo.php/f' => [
                                    'lineCoverage'     => [],
                                    'functionCoverage' => [],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            $method->invoke(
                $this->factory,
                $this->pathsToProcessedDataObjectHelper([
                    "src{$s}Money.php"                    => [],
                    "src{$s}MoneyBag.php"                 => [],
                    "src{$s}Foo{$s}Bar{$s}Baz{$s}Foo.php" => [],
                ])
            )
        );
    }

    /**
     * @dataProvider reducePathsProvider
     */
    public function testReducePaths(array $reducedPaths, string $commonPath, ProcessedCodeCoverageData $paths): void
    {
        $method = new ReflectionMethod(
            Builder::class,
            'reducePaths'
        );

        $method->setAccessible(true);

        $_commonPath = $method->invokeArgs($this->factory, [$paths]);

        $this->assertEquals($reducedPaths, $paths->lineCoverage());
        $this->assertEquals($commonPath, $_commonPath);
    }

    public function reducePathsProvider()
    {
        $s = DIRECTORY_SEPARATOR;

        yield [
            [],
            '.',
            $this->pathsToProcessedDataObjectHelper([]),
        ];

        foreach (["C:$s", "$s"] as $p) {
            yield [
                [
                    'Money.php' => [],
                ],
                "{$p}home{$s}sb{$s}Money{$s}",
                $this->pathsToProcessedDataObjectHelper([
                    "{$p}home{$s}sb{$s}Money{$s}Money.php" => [],
                ]),
            ];

            yield [
                [
                    'Money.php'    => [],
                    'MoneyBag.php' => [],
                ],
                "{$p}home{$s}sb{$s}Money",
                $this->pathsToProcessedDataObjectHelper([
                    "{$p}home{$s}sb{$s}Money{$s}Money.php"    => [],
                    "{$p}home{$s}sb{$s}Money{$s}MoneyBag.php" => [],
                ]),
            ];

            yield [
                [
                    'Money.php'             => [],
                    'MoneyBag.php'          => [],
                    "Cash.phar{$s}Cash.php" => [],
                ],
                "{$p}home{$s}sb{$s}Money",
                $this->pathsToProcessedDataObjectHelper([
                    "{$p}home{$s}sb{$s}Money{$s}Money.php"                    => [],
                    "{$p}home{$s}sb{$s}Money{$s}MoneyBag.php"                 => [],
                    "phar://{$p}home{$s}sb{$s}Money{$s}Cash.phar{$s}Cash.php" => [],
                ]),
            ];
        }
    }

    private function pathsToProcessedDataObjectHelper(array $paths): ProcessedCodeCoverageData
    {
        $coverage = new ProcessedCodeCoverageData;
        $coverage->setLineCoverage($paths);

        return $coverage;
    }
}
