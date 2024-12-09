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
use function range;
use function realpath;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\StaticAnalysis\ParsingFileAnalyser;

/**
 * @phpstan-import-type TargetMap from Mapper
 */
#[CoversClass(Mapper::class)]
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
                    $file => range(33, 52),
                ],
                TargetCollection::fromArray(
                    [
                        Target::forClass('SebastianBergmann\\CodeCoverage\\StaticAnalysis\\ChildClass'),
                    ],
                ),
            ],
            'classes that extend class' => [
                [
                    $file => range(33, 52),
                ],
                TargetCollection::fromArray(
                    [
                        Target::forClassesThatExtendClass('SebastianBergmann\\CodeCoverage\\StaticAnalysis\\ParentClass'),
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
            'method' => [
                [
                    $file => range(37, 39),
                ],
                TargetCollection::fromArray(
                    [
                        Target::forMethod('SebastianBergmann\\CodeCoverage\\StaticAnalysis\\ChildClass', 'six'),
                    ],
                ),
            ],
            'methods' => [
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

        return (new MapBuilder)->build($filter, new ParsingFileAnalyser(false, false));
    }
}
