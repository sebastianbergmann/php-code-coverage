<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Test\Target;

use function array_keys;
use function array_merge;
use function dirname;
use function range;
use function realpath;
use function strtolower;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\StaticAnalysis\FileAnalyser;
use SebastianBergmann\CodeCoverage\StaticAnalysis\ParsingSourceAnalyser;
use SebastianBergmann\CodeCoverage\TestFixture\Target\GrandParentClass;
use SebastianBergmann\CodeCoverage\TestFixture\Target\Issue1066\DummyWithTrait;
use SebastianBergmann\CodeCoverage\TestFixture\Target\TargetClass;
use SebastianBergmann\CodeCoverage\TestFixture\Target\TargetEnumeration;
use SebastianBergmann\CodeCoverage\TestFixture\Target\TraitOne;

/**
 * @phpstan-import-type TargetMap from Mapper
 */
#[CoversClass(Mapper::class)]
#[CoversClass(InvalidCodeCoverageTargetException::class)]
#[Small]
final class MapperTest extends TestCase
{
    /**
     * @return non-empty-array<non-empty-string, array{0: array<non-empty-string, non-empty-list<positive-int>>, 1: TargetCollection}>
     */
    public static function provider(): array
    {
        $file = realpath(__DIR__ . '/../../_files/source_with_interfaces_classes_traits_functions.php');

        return [
            'class' => [
                [
                    $file => array_merge(
                        range(33, 52),
                        range(19, 24),
                        range(26, 31),
                    ),
                ],
                TargetCollection::fromArray(
                    [
                        Target::forClass('SebastianBergmann\\CodeCoverage\\StaticAnalysis\\ChildClass'),
                    ],
                ),
            ],

            'class (which uses traits)' => [
                [
                    realpath(__DIR__ . '/../../_files/Target/ClassUsingTraitUsingTrait.php') => range(4, 11),
                    realpath(__DIR__ . '/../../_files/Target/TraitTwo.php')                  => range(4, 11),
                    realpath(__DIR__ . '/../../_files/Target/TraitOne.php')                  => range(4, 9),
                ],
                TargetCollection::fromArray(
                    [
                        Target::forClass('SebastianBergmann\\CodeCoverage\\TestFixture\\Target\\ClassUsingTraitUsingTrait'),
                    ],
                ),
            ],

            'classes that extend class (parent and child)' => [
                [
                    $file => range(33, 52),
                ],
                TargetCollection::fromArray(
                    [
                        Target::forClassesThatExtendClass('SebastianBergmann\\CodeCoverage\\StaticAnalysis\\ParentClass'),
                    ],
                ),
            ],

            'classes that extend class (grand parent, parent, and child)' => [
                [
                    realpath(__DIR__ . '/../../_files/Target/GrandParentClass.php') => range(4, 9),
                    realpath(__DIR__ . '/../../_files/Target/ParentClass.php')      => range(4, 9),
                    realpath(__DIR__ . '/../../_files/Target/ChildClass.php')       => range(4, 9),
                ],
                TargetCollection::fromArray(
                    [
                        Target::forClass(GrandParentClass::class),
                        Target::forClassesThatExtendClass(GrandParentClass::class),
                    ],
                ),
            ],

            'classes that implement interface' => [
                [
                    $file => range(26, 31),
                ],
                TargetCollection::fromArray(
                    [
                        Target::forClassesThatImplementInterface('SebastianBergmann\\CodeCoverage\\StaticAnalysis\\C'),
                    ],
                ),
            ],

            'trait' => [
                [
                    realpath(__DIR__ . '/../../_files/Target/TraitOne.php') => range(4, 9),
                ],
                TargetCollection::fromArray(
                    [
                        Target::forTrait(TraitOne::class),
                    ],
                ),
            ],

            'function' => [
                [
                    $file => range(54, 56),
                ],
                TargetCollection::fromArray(
                    [
                        Target::forFunction('SebastianBergmann\\CodeCoverage\\StaticAnalysis\\f'),
                    ],
                ),
            ],

            'method of class' => [
                [
                    $file => range(37, 39),
                ],
                TargetCollection::fromArray(
                    [
                        Target::forMethod('SebastianBergmann\\CodeCoverage\\StaticAnalysis\\ChildClass', 'six'),
                    ],
                ),
            ],

            'methods of class' => [
                [
                    $file => array_merge(range(37, 39), range(41, 43)),
                ],
                TargetCollection::fromArray(
                    [
                        Target::forMethod('SebastianBergmann\\CodeCoverage\\StaticAnalysis\\ChildClass', 'six'),
                        Target::forMethod('SebastianBergmann\\CodeCoverage\\StaticAnalysis\\ChildClass', 'one'),
                    ],
                ),
            ],

            'method of trait' => [
                [
                    $file => range(21, 23),
                ],
                TargetCollection::fromArray(
                    [
                        Target::forMethod('SebastianBergmann\\CodeCoverage\\StaticAnalysis\\T', 'four'),
                    ],
                ),
            ],

            'namespace' => [
                [
                    $file => array_merge(
                        range(19, 24),
                        range(26, 31),
                        range(33, 52),
                        range(54, 56),
                    ),
                ],
                TargetCollection::fromArray(
                    [
                        Target::forNamespace('SebastianBergmann\\CodeCoverage\\StaticAnalysis'),
                    ],
                ),
            ],

            'enumeration' => [
                [
                    realpath(__DIR__ . '/../../_files/Target/TargetEnumeration.php') => range(4, 8),
                ],
                TargetCollection::fromArray(
                    [
                        Target::forClass(TargetEnumeration::class),
                    ],
                ),
            ],
        ];
    }

    /**
     * @return non-empty-array<non-empty-string, array{0: non-empty-string, 1: TargetCollection}>
     */
    public static function invalidProvider(): array
    {
        return [
            'class' => [
                'Class DoesNotExist is not a valid target for code coverage',
                TargetCollection::fromArray(
                    [
                        /** @phpstan-ignore argument.type */
                        Target::forClass('DoesNotExist'),
                    ],
                ),
            ],
            'classes that extend class' => [
                'Classes that extend class DoesNotExist is not a valid target for code coverage',
                TargetCollection::fromArray(
                    [
                        /** @phpstan-ignore argument.type */
                        Target::forClassesThatExtendClass('DoesNotExist'),
                    ],
                ),
            ],
            'classes that implement interface' => [
                'Classes that implement interface DoesNotExist is not a valid target for code coverage',
                TargetCollection::fromArray(
                    [
                        /** @phpstan-ignore argument.type */
                        Target::forClassesThatImplementInterface('DoesNotExist'),
                    ],
                ),
            ],
            'function' => [
                'Function does_not_exist is not a valid target for code coverage',
                TargetCollection::fromArray(
                    [
                        Target::forFunction('does_not_exist'),
                    ],
                ),
            ],
            'method' => [
                'Method DoesNotExist::doesNotExist is not a valid target for code coverage',
                TargetCollection::fromArray(
                    [
                        /** @phpstan-ignore argument.type */
                        Target::forMethod('DoesNotExist', 'doesNotExist'),
                    ],
                ),
            ],
            'namespace' => [
                'Namespace DoesNotExist is not a valid target for code coverage',
                TargetCollection::fromArray(
                    [
                        Target::forNamespace('DoesNotExist'),
                    ],
                ),
            ],
            'file' => [
                'File /does/not/exist.php is not a valid target for code coverage',
                TargetCollection::fromArray(
                    [
                        Target::forFile('/does/not/exist.php'),
                    ],
                ),
            ],
            'directory' => [
                'Directory /does/not/exist is not a valid target for code coverage',
                TargetCollection::fromArray(
                    [
                        Target::forDirectory('/does/not/exist'),
                    ],
                ),
            ],
        ];
    }

    /**
     * @param array<non-empty-string, non-empty-list<positive-int>> $expected
     */
    #[DataProvider('provider')]
    #[TestDox('Maps TargetCollection with $_dataName to source locations')]
    public function testMapsTargetValueObjectsToSourceLocations(array $expected, TargetCollection $targets): void
    {
        $this->assertSame(
            $expected,
            $this->mapper(array_keys($expected))->mapTargets($targets),
        );
    }

    #[DataProvider('invalidProvider')]
    #[TestDox('Cannot map $_dataName that does not exist to source location')]
    public function testCannotMapInvalidTargets(string $exceptionMessage, TargetCollection $targets): void
    {
        $this->expectException(InvalidCodeCoverageTargetException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->mapper([])->mapTargets($targets);
    }

    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/1064')]
    public function testCodeUnitTargetingIsCaseInsensitive(): void
    {
        $path   = realpath(__DIR__ . '/../../_files/Target/TargetClass.php');
        $mapper = $this->mapper([$path]);

        $this->assertSame(
            [
                $path => range(4, 9),
            ],
            $mapper->mapTarget(
                Target::forClass(strtolower(TargetClass::class)),
            ),
        );
    }

    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/1066')]
    public function testIssue1066(): void
    {
        $baseDummy      = realpath(__DIR__ . '/../../_files/Target/regression/1066/BaseDummy.php');
        $dummy          = realpath(__DIR__ . '/../../_files/Target/regression/1066/Dummy.php');
        $dummy2         = realpath(__DIR__ . '/../../_files/Target/regression/1066/Dummy2.php');
        $dummyWithTrait = realpath(__DIR__ . '/../../_files/Target/regression/1066/DummyWithTrait.php');
        $someTrait      = realpath(__DIR__ . '/../../_files/Target/regression/1066/SomeTrait.php');

        $mapper = $this->mapper(
            [
                $baseDummy,
                $dummy,
                $dummy2,
                $dummyWithTrait,
                $someTrait,
            ],
        );

        $this->assertSame(
            [
                $dummyWithTrait => range(8, 11),
            ],
            $mapper->mapTarget(
                Target::forMethod(DummyWithTrait::class, 'method1'),
            ),
        );

        $this->assertSame(
            DummyWithTrait::class . '::method1',
            $mapper->lookup($dummyWithTrait, 10),
        );
    }

    public function testLineOfCodeInGlobalScopeDoesNotBelongToCodeUnit(): void
    {
        $file   = realpath(__DIR__ . '/../../_files/source_without_ignore.php');
        $mapper = $this->mapper([$file]);

        $this->assertSame($file . ':2', $mapper->lookup($file, 2));
    }

    public function testCanMapFileTarget(): void
    {
        $file     = realpath(__DIR__ . '/../../_files/source_without_ignore.php');
        $analyser = new FileAnalyser(new ParsingSourceAnalyser, false, false);

        $expectedLines = array_keys($analyser->analyse($file)->executableLines());

        $this->assertNotEmpty($expectedLines);
        $this->assertSame(
            [$file => $expectedLines],
            $this->mapper([$file])->mapTarget(Target::forFile($file)),
        );
    }

    public function testCanMapDirectoryTarget(): void
    {
        $file     = realpath(__DIR__ . '/../../_files/source_without_ignore.php');
        $dir      = dirname($file);
        $analyser = new FileAnalyser(new ParsingSourceAnalyser, false, false);

        $expectedLines = array_keys($analyser->analyse($file)->executableLines());

        $this->assertNotEmpty($expectedLines);

        $result = $this->mapper([$file])->mapTarget(Target::forDirectory($dir));

        $this->assertArrayHasKey($file, $result);
        $this->assertSame($expectedLines, $result[$file]);
    }

    /**
     * @param list<non-empty-string> $files
     */
    private function mapper(array $files): Mapper
    {
        return new Mapper($this->map($files));
    }

    /**
     * @param list<non-empty-string> $files
     *
     * @return TargetMap
     */
    private function map(array $files): array
    {
        $filter = new Filter;

        $filter->includeFiles($files);

        return (new MapBuilder)->build(
            $filter,
            new FileAnalyser(
                new ParsingSourceAnalyser,
                false,
                false,
            ),
        );
    }
}
