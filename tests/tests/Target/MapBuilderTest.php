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

use function array_merge;
use function range;
use function realpath;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\StaticAnalysis\FileAnalyser;
use SebastianBergmann\CodeCoverage\StaticAnalysis\ParsingSourceAnalyser;
use SebastianBergmann\CodeCoverage\TestFixture\Target\ChildClass;
use SebastianBergmann\CodeCoverage\TestFixture\Target\GrandParentClass;
use SebastianBergmann\CodeCoverage\TestFixture\Target\Issue1066\BaseDummy;
use SebastianBergmann\CodeCoverage\TestFixture\Target\Issue1066\Dummy;
use SebastianBergmann\CodeCoverage\TestFixture\Target\Issue1066\Dummy2;
use SebastianBergmann\CodeCoverage\TestFixture\Target\Issue1066\DummyWithTrait;
use SebastianBergmann\CodeCoverage\TestFixture\Target\Issue1066\SomeTrait;
use SebastianBergmann\CodeCoverage\TestFixture\Target\ParentClass;
use SebastianBergmann\CodeCoverage\TestFixture\Target\T1;
use SebastianBergmann\CodeCoverage\TestFixture\Target\T2;
use SebastianBergmann\CodeCoverage\TestFixture\Target\TargetEnumeration;
use SebastianBergmann\CodeCoverage\TestFixture\Target\TraitOne;
use SebastianBergmann\CodeCoverage\TestFixture\Target\TraitTwo;

/**
 * @phpstan-import-type TargetMap from Mapper
 */
#[CoversClass(MapBuilder::class)]
#[Small]
final class MapBuilderTest extends TestCase
{
    /**
     * @return non-empty-array<non-empty-string, array{0: TargetMap, 1: non-empty-list<non-empty-string>}>
     */
    public static function provider(): array
    {
        $file             = realpath(__DIR__ . '/../../_files/source_with_interfaces_classes_traits_functions.php');
        $traitOne         = realpath(__DIR__ . '/../../_files/Target/TraitOne.php');
        $traitTwo         = realpath(__DIR__ . '/../../_files/Target/TraitTwo.php');
        $twoTraits        = realpath(__DIR__ . '/../../_files/Target/two_traits.php');
        $enum             = realpath(__DIR__ . '/../../_files/Target/TargetEnumeration.php');
        $grandParentClass = realpath(__DIR__ . '/../../_files/Target/GrandParentClass.php');
        $parentClass      = realpath(__DIR__ . '/../../_files/Target/ParentClass.php');
        $childClass       = realpath(__DIR__ . '/../../_files/Target/ChildClass.php');

        return [
            'generic' => [
                [
                    'namespaces' => [
                        'SebastianBergmann' => [
                            $file => array_merge(
                                range(19, 24),
                                range(26, 31),
                                range(33, 52),
                                range(54, 56),
                            ),
                        ],
                        'SebastianBergmann\\CodeCoverage' => [
                            $file => array_merge(
                                range(19, 24),
                                range(26, 31),
                                range(33, 52),
                                range(54, 56),
                            ),
                        ],
                        'SebastianBergmann\\CodeCoverage\\StaticAnalysis' => [
                            $file => array_merge(
                                range(19, 24),
                                range(26, 31),
                                range(33, 52),
                                range(54, 56),
                            ),
                        ],
                    ],
                    'traits' => [
                        'SebastianBergmann\\CodeCoverage\\StaticAnalysis\\T' => [
                            $file => range(19, 24),
                        ],
                    ],
                    'classes' => [
                        'SebastianBergmann\\CodeCoverage\\StaticAnalysis\\ParentClass' => [
                            $file => range(26, 31),
                        ],
                        'SebastianBergmann\\CodeCoverage\\StaticAnalysis\\ChildClass' => [
                            $file => array_merge(
                                range(33, 52),
                                range(19, 24),
                                range(26, 31),
                            ),
                        ],
                    ],
                    'classesThatExtendClass' => [
                        'SebastianBergmann\\CodeCoverage\\StaticAnalysis\\ParentClass' => [
                            $file => range(33, 52),
                        ],
                    ],
                    'classesThatImplementInterface' => [
                        'SebastianBergmann\\CodeCoverage\\StaticAnalysis\\A' => [
                            $file => range(33, 52),
                        ],
                        'SebastianBergmann\\CodeCoverage\\StaticAnalysis\\B' => [
                            $file => range(33, 52),
                        ],
                        'SebastianBergmann\\CodeCoverage\\StaticAnalysis\\C' => [
                            $file => range(26, 31),
                        ],
                    ],
                    'methods' => [
                        'SebastianBergmann\\CodeCoverage\\StaticAnalysis\\T::four' => [
                            $file => range(21, 23),
                        ],
                        'SebastianBergmann\\CodeCoverage\\StaticAnalysis\\ParentClass::five' => [
                            $file => range(28, 30),
                        ],
                        'SebastianBergmann\\CodeCoverage\\StaticAnalysis\\ChildClass::six' => [
                            $file => range(37, 39),
                        ],
                        'SebastianBergmann\\CodeCoverage\\StaticAnalysis\\ChildClass::one' => [
                            $file => range(41, 43),
                        ],
                        'SebastianBergmann\\CodeCoverage\\StaticAnalysis\\ChildClass::two' => [
                            $file => range(45, 47),
                        ],
                        'SebastianBergmann\\CodeCoverage\\StaticAnalysis\\ChildClass::three' => [
                            $file => range(49, 51),
                        ],
                    ],
                    'functions' => [
                        'SebastianBergmann\\CodeCoverage\\StaticAnalysis\\f' => [
                            $file => range(54, 56),
                        ],
                    ],
                    'reverseLookup' => [
                        $file . ':21' => 'SebastianBergmann\CodeCoverage\StaticAnalysis\T::four',
                        $file . ':22' => 'SebastianBergmann\CodeCoverage\StaticAnalysis\T::four',
                        $file . ':23' => 'SebastianBergmann\CodeCoverage\StaticAnalysis\T::four',
                        $file . ':28' => 'SebastianBergmann\CodeCoverage\StaticAnalysis\ParentClass::five',
                        $file . ':29' => 'SebastianBergmann\CodeCoverage\StaticAnalysis\ParentClass::five',
                        $file . ':30' => 'SebastianBergmann\CodeCoverage\StaticAnalysis\ParentClass::five',
                        $file . ':37' => 'SebastianBergmann\CodeCoverage\StaticAnalysis\ChildClass::six',
                        $file . ':38' => 'SebastianBergmann\CodeCoverage\StaticAnalysis\ChildClass::six',
                        $file . ':39' => 'SebastianBergmann\CodeCoverage\StaticAnalysis\ChildClass::six',
                        $file . ':41' => 'SebastianBergmann\CodeCoverage\StaticAnalysis\ChildClass::one',
                        $file . ':42' => 'SebastianBergmann\CodeCoverage\StaticAnalysis\ChildClass::one',
                        $file . ':43' => 'SebastianBergmann\CodeCoverage\StaticAnalysis\ChildClass::one',
                        $file . ':45' => 'SebastianBergmann\CodeCoverage\StaticAnalysis\ChildClass::two',
                        $file . ':46' => 'SebastianBergmann\CodeCoverage\StaticAnalysis\ChildClass::two',
                        $file . ':47' => 'SebastianBergmann\CodeCoverage\StaticAnalysis\ChildClass::two',
                        $file . ':49' => 'SebastianBergmann\CodeCoverage\StaticAnalysis\ChildClass::three',
                        $file . ':50' => 'SebastianBergmann\CodeCoverage\StaticAnalysis\ChildClass::three',
                        $file . ':51' => 'SebastianBergmann\CodeCoverage\StaticAnalysis\ChildClass::three',
                        $file . ':54' => 'SebastianBergmann\CodeCoverage\StaticAnalysis\f',
                        $file . ':55' => 'SebastianBergmann\CodeCoverage\StaticAnalysis\f',
                        $file . ':56' => 'SebastianBergmann\CodeCoverage\StaticAnalysis\f',
                    ],
                ],
                [$file],
            ],
            'trait using trait declared in another file' => [
                [
                    'namespaces' => [
                        'SebastianBergmann' => [
                            $traitOne => range(4, 9),
                            $traitTwo => range(4, 11),
                        ],
                        'SebastianBergmann\\CodeCoverage' => [
                            $traitOne => range(4, 9),
                            $traitTwo => range(4, 11),
                        ],
                        'SebastianBergmann\\CodeCoverage\\TestFixture' => [
                            $traitOne => range(4, 9),
                            $traitTwo => range(4, 11),
                        ],
                        'SebastianBergmann\\CodeCoverage\\TestFixture\\Target' => [
                            $traitOne => range(4, 9),
                            $traitTwo => range(4, 11),
                        ],
                    ],
                    'traits' => [
                        TraitOne::class => [
                            $traitOne => range(4, 9),
                        ],
                        TraitTwo::class => [
                            $traitTwo => range(4, 11),
                            $traitOne => range(4, 9),
                        ],
                    ],
                    'classes' => [
                    ],
                    'classesThatExtendClass' => [
                    ],
                    'classesThatImplementInterface' => [
                    ],
                    'methods' => [
                        TraitOne::class . '::one' => [
                            $traitOne => range(6, 8),
                        ],
                        TraitTwo::class . '::two' => [
                            $traitTwo => range(8, 10),
                        ],
                    ],
                    'functions' => [
                    ],
                    'reverseLookup' => [
                        $traitOne . ':6'  => TraitOne::class . '::one',
                        $traitOne . ':7'  => TraitOne::class . '::one',
                        $traitOne . ':8'  => TraitOne::class . '::one',
                        $traitTwo . ':8'  => TraitTwo::class . '::two',
                        $traitTwo . ':9'  => TraitTwo::class . '::two',
                        $traitTwo . ':10' => TraitTwo::class . '::two',
                    ],
                ],
                [
                    $traitOne,
                    $traitTwo,
                ],
            ],
            'trait using trait declared in same file' => [
                [
                    'namespaces' => [
                        'SebastianBergmann' => [
                            $twoTraits => array_merge(
                                range(4, 9),
                                range(11, 18),
                            ),
                        ],
                        'SebastianBergmann\\CodeCoverage' => [
                            $twoTraits => array_merge(
                                range(4, 9),
                                range(11, 18),
                            ),
                        ],
                        'SebastianBergmann\\CodeCoverage\\TestFixture' => [
                            $twoTraits => array_merge(
                                range(4, 9),
                                range(11, 18),
                            ),
                        ],
                        'SebastianBergmann\\CodeCoverage\\TestFixture\\Target' => [
                            $twoTraits => array_merge(
                                range(4, 9),
                                range(11, 18),
                            ),
                        ],
                    ],
                    'traits' => [
                        T1::class => [
                            $twoTraits => range(4, 9),
                        ],
                        T2::class => [
                            $twoTraits => array_merge(
                                range(11, 18),
                                range(4, 9),
                            ),
                        ],
                    ],
                    'classes' => [
                    ],
                    'classesThatExtendClass' => [
                    ],
                    'classesThatImplementInterface' => [
                    ],
                    'methods' => [
                        T1::class . '::one' => [
                            $twoTraits => range(6, 8),
                        ],
                        T2::class . '::two' => [
                            $twoTraits => range(15, 17),
                        ],
                    ],
                    'functions' => [
                    ],
                    'reverseLookup' => [
                        $twoTraits . ':6'  => T1::class . '::one',
                        $twoTraits . ':7'  => T1::class . '::one',
                        $twoTraits . ':8'  => T1::class . '::one',
                        $twoTraits . ':15' => T2::class . '::two',
                        $twoTraits . ':16' => T2::class . '::two',
                        $twoTraits . ':17' => T2::class . '::two',
                    ],
                ],
                [
                    $twoTraits,
                ],
            ],
            'enumeration' => [
                [
                    'namespaces' => [
                        'SebastianBergmann' => [
                            $enum => range(4, 8),
                        ],
                        'SebastianBergmann\\CodeCoverage' => [
                            $enum => range(4, 8),
                        ],
                        'SebastianBergmann\\CodeCoverage\\TestFixture' => [
                            $enum => range(4, 8),
                        ],
                        'SebastianBergmann\\CodeCoverage\\TestFixture\\Target' => [
                            $enum => range(4, 8),
                        ],
                    ],
                    'traits' => [
                    ],
                    'classes' => [
                        TargetEnumeration::class => [
                            $enum => range(4, 8),
                        ],
                    ],
                    'classesThatExtendClass' => [
                    ],
                    'classesThatImplementInterface' => [
                    ],
                    'methods' => [
                    ],
                    'functions' => [
                    ],
                    'reverseLookup' => [
                    ],
                ],
                [
                    $enum,
                ],
            ],
            'class with parent classes' => [
                [
                    'namespaces' => [
                        'SebastianBergmann' => [
                            $grandParentClass => range(4, 9),
                            $parentClass      => range(4, 9),
                            $childClass       => range(4, 9),
                        ],
                        'SebastianBergmann\CodeCoverage' => [
                            $grandParentClass => range(4, 9),
                            $parentClass      => range(4, 9),
                            $childClass       => range(4, 9),
                        ],
                        'SebastianBergmann\CodeCoverage\TestFixture' => [
                            $grandParentClass => range(4, 9),
                            $parentClass      => range(4, 9),
                            $childClass       => range(4, 9),
                        ],
                        'SebastianBergmann\CodeCoverage\TestFixture\Target' => [
                            $grandParentClass => range(4, 9),
                            $parentClass      => range(4, 9),
                            $childClass       => range(4, 9),
                        ],
                    ],
                    'traits' => [
                    ],
                    'classes' => [
                        GrandParentClass::class => [
                            $grandParentClass => range(4, 9),
                        ],
                        ParentClass::class => [
                            $parentClass      => range(4, 9),
                            $grandParentClass => range(4, 9),
                        ],
                        ChildClass::class => [
                            $childClass       => range(4, 9),
                            $parentClass      => range(4, 9),
                            $grandParentClass => range(4, 9),
                        ],
                    ],
                    'classesThatExtendClass' => [
                        GrandParentClass::class => [
                            $parentClass => range(4, 9),
                            $childClass  => range(4, 9),
                        ],
                        ParentClass::class => [
                            $childClass => range(4, 9),
                        ],
                    ],
                    'classesThatImplementInterface' => [
                    ],
                    'methods' => [
                        GrandParentClass::class . '::one' => [
                            $grandParentClass => range(6, 8),
                        ],
                        ParentClass::class . '::two' => [
                            $parentClass => range(6, 8),
                        ],
                        ChildClass::class . '::three' => [
                            $childClass => range(6, 8),
                        ],
                    ],
                    'functions' => [
                    ],
                    'reverseLookup' => [
                        $grandParentClass . ':6' => 'SebastianBergmann\CodeCoverage\TestFixture\Target\GrandParentClass::one',
                        $grandParentClass . ':7' => 'SebastianBergmann\CodeCoverage\TestFixture\Target\GrandParentClass::one',
                        $grandParentClass . ':8' => 'SebastianBergmann\CodeCoverage\TestFixture\Target\GrandParentClass::one',
                        $parentClass . ':6'      => 'SebastianBergmann\CodeCoverage\TestFixture\Target\ParentClass::two',
                        $parentClass . ':7'      => 'SebastianBergmann\CodeCoverage\TestFixture\Target\ParentClass::two',
                        $parentClass . ':8'      => 'SebastianBergmann\CodeCoverage\TestFixture\Target\ParentClass::two',
                        $childClass . ':6'       => 'SebastianBergmann\CodeCoverage\TestFixture\Target\ChildClass::three',
                        $childClass . ':7'       => 'SebastianBergmann\CodeCoverage\TestFixture\Target\ChildClass::three',
                        $childClass . ':8'       => 'SebastianBergmann\CodeCoverage\TestFixture\Target\ChildClass::three',
                    ],
                ],
                [
                    $grandParentClass,
                    $parentClass,
                    $childClass,
                ],
            ],
        ];
    }

    /**
     * @param TargetMap                        $expected
     * @param non-empty-list<non-empty-string> $files
     */
    #[DataProvider('provider')]
    public function testBuildsMap(array $expected, array $files): void
    {
        $this->assertSame($expected, $this->map($files));
    }

    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/1066')]
    public function testIssue1066(): void
    {
        $baseDummy      = realpath(__DIR__ . '/../../_files/Target/regression/1066/BaseDummy.php');
        $dummy          = realpath(__DIR__ . '/../../_files/Target/regression/1066/Dummy.php');
        $dummy2         = realpath(__DIR__ . '/../../_files/Target/regression/1066/Dummy2.php');
        $dummyWithTrait = realpath(__DIR__ . '/../../_files/Target/regression/1066/DummyWithTrait.php');
        $someTrait      = realpath(__DIR__ . '/../../_files/Target/regression/1066/SomeTrait.php');

        $this->assertSame(
            [
                'namespaces' => [
                    'SebastianBergmann' => [
                        $someTrait      => range(4, 6),
                        $baseDummy      => range(4, 6),
                        $dummy          => range(4, 15),
                        $dummy2         => range(4, 15),
                        $dummyWithTrait => range(4, 17),
                    ],
                    'SebastianBergmann\CodeCoverage' => [
                        $someTrait      => range(4, 6),
                        $baseDummy      => range(4, 6),
                        $dummy          => range(4, 15),
                        $dummy2         => range(4, 15),
                        $dummyWithTrait => range(4, 17),
                    ],
                    'SebastianBergmann\CodeCoverage\TestFixture' => [
                        $someTrait      => range(4, 6),
                        $baseDummy      => range(4, 6),
                        $dummy          => range(4, 15),
                        $dummy2         => range(4, 15),
                        $dummyWithTrait => range(4, 17),
                    ],
                    'SebastianBergmann\CodeCoverage\TestFixture\Target' => [
                        $someTrait      => range(4, 6),
                        $baseDummy      => range(4, 6),
                        $dummy          => range(4, 15),
                        $dummy2         => range(4, 15),
                        $dummyWithTrait => range(4, 17),
                    ],
                    'SebastianBergmann\CodeCoverage\TestFixture\Target\Issue1066' => [
                        $someTrait      => range(4, 6),
                        $baseDummy      => range(4, 6),
                        $dummy          => range(4, 15),
                        $dummy2         => range(4, 15),
                        $dummyWithTrait => range(4, 17),
                    ],
                ],
                'traits' => [
                    SomeTrait::class => [
                        $someTrait => range(4, 6),
                    ],
                ],
                'classes' => [
                    BaseDummy::class => [
                        $baseDummy => range(4, 6),
                    ],
                    Dummy::class => [
                        $dummy     => range(4, 15),
                        $baseDummy => range(4, 6),
                    ],
                    Dummy2::class => [
                        $dummy2 => range(4, 15),
                    ],
                    DummyWithTrait::class => [
                        $dummyWithTrait => range(4, 17),
                        $someTrait      => range(4, 6),
                        $baseDummy      => range(4, 6),
                    ],
                ],
                'classesThatExtendClass' => [
                    BaseDummy::class => [
                        $dummy          => range(4, 15),
                        $dummyWithTrait => range(4, 17),
                    ],
                ],
                'classesThatImplementInterface' => [
                ],
                'methods' => [
                    Dummy::class . '::method1' => [
                        $dummy => range(6, 9),
                    ],
                    Dummy::class . '::method2' => [
                        $dummy => range(11, 14),
                    ],
                    Dummy2::class . '::method1' => [
                        $dummy2 => range(6, 9),
                    ],
                    Dummy2::class . '::method2' => [
                        $dummy2 => range(11, 14),
                    ],
                    DummyWithTrait::class . '::method1' => [
                        $dummyWithTrait => range(8, 11),
                    ],
                    DummyWithTrait::class . '::method2' => [
                        $dummyWithTrait => range(13, 16),
                    ],
                ],
                'functions' => [
                ],
                'reverseLookup' => [
                    $dummy . ':6'           => Dummy::class . '::method1',
                    $dummy . ':7'           => Dummy::class . '::method1',
                    $dummy . ':8'           => Dummy::class . '::method1',
                    $dummy . ':9'           => Dummy::class . '::method1',
                    $dummy . ':11'          => Dummy::class . '::method2',
                    $dummy . ':12'          => Dummy::class . '::method2',
                    $dummy . ':13'          => Dummy::class . '::method2',
                    $dummy . ':14'          => Dummy::class . '::method2',
                    $dummy2 . ':6'          => Dummy2::class . '::method1',
                    $dummy2 . ':7'          => Dummy2::class . '::method1',
                    $dummy2 . ':8'          => Dummy2::class . '::method1',
                    $dummy2 . ':9'          => Dummy2::class . '::method1',
                    $dummy2 . ':11'         => Dummy2::class . '::method2',
                    $dummy2 . ':12'         => Dummy2::class . '::method2',
                    $dummy2 . ':13'         => Dummy2::class . '::method2',
                    $dummy2 . ':14'         => Dummy2::class . '::method2',
                    $dummyWithTrait . ':8'  => DummyWithTrait::class . '::method1',
                    $dummyWithTrait . ':9'  => DummyWithTrait::class . '::method1',
                    $dummyWithTrait . ':10' => DummyWithTrait::class . '::method1',
                    $dummyWithTrait . ':11' => DummyWithTrait::class . '::method1',
                    $dummyWithTrait . ':13' => DummyWithTrait::class . '::method2',
                    $dummyWithTrait . ':14' => DummyWithTrait::class . '::method2',
                    $dummyWithTrait . ':15' => DummyWithTrait::class . '::method2',
                    $dummyWithTrait . ':16' => DummyWithTrait::class . '::method2',
                ],
            ],
            $this->map(
                [
                    $baseDummy,
                    $dummy,
                    $dummy2,
                    $dummyWithTrait,
                    $someTrait,
                ],
            ),
        );
    }

    /**
     * @param list<string> $files
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
