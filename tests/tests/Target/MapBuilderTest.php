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
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\StaticAnalysis\ParsingFileAnalyser;

#[CoversClass(MapBuilder::class)]
#[Small]
final class MapBuilderTest extends TestCase
{
    public function testBuildsMap(): void
    {
        $this->assertSame(
            [
                'namespaces' => [
                    'SebastianBergmann\\CodeCoverage\\StaticAnalysis' => [
                        realpath(__DIR__ . '/../../_files/source_with_interfaces_classes_traits_functions.php') => array_merge(
                            range(19, 24),
                            range(26, 31),
                            range(33, 52),
                            range(54, 56),
                        ),
                    ],
                ],
                'classes' => [
                    'SebastianBergmann\\CodeCoverage\\StaticAnalysis\\ParentClass' => [
                        realpath(__DIR__ . '/../../_files/source_with_interfaces_classes_traits_functions.php') => range(26, 31),
                    ],
                    'SebastianBergmann\\CodeCoverage\\StaticAnalysis\\ChildClass' => [
                        realpath(__DIR__ . '/../../_files/source_with_interfaces_classes_traits_functions.php') => range(33, 52),
                    ],
                ],
                'classesThatExtendClass' => [
                    'SebastianBergmann\\CodeCoverage\\StaticAnalysis\\ParentClass' => [
                        realpath(__DIR__ . '/../../_files/source_with_interfaces_classes_traits_functions.php') => range(33, 52),
                    ],
                ],
                'classesThatImplementInterface' => [
                    'SebastianBergmann\\CodeCoverage\\StaticAnalysis\\A' => [
                        realpath(__DIR__ . '/../../_files/source_with_interfaces_classes_traits_functions.php') => range(33, 52),
                    ],
                    'SebastianBergmann\\CodeCoverage\\StaticAnalysis\\B' => [
                        realpath(__DIR__ . '/../../_files/source_with_interfaces_classes_traits_functions.php') => range(33, 52),
                    ],
                    'SebastianBergmann\\CodeCoverage\\StaticAnalysis\\C' => [
                        realpath(__DIR__ . '/../../_files/source_with_interfaces_classes_traits_functions.php') => range(26, 31),
                    ],
                ],
                'traits' => [
                    'SebastianBergmann\\CodeCoverage\\StaticAnalysis\\T' => [
                        realpath(__DIR__ . '/../../_files/source_with_interfaces_classes_traits_functions.php') => range(19, 24),
                    ],
                ],
                'methods' => [
                    'SebastianBergmann\\CodeCoverage\\StaticAnalysis\\ParentClass::five' => [
                        realpath(__DIR__ . '/../../_files/source_with_interfaces_classes_traits_functions.php') => range(28, 30),
                    ],
                    'SebastianBergmann\\CodeCoverage\\StaticAnalysis\\ChildClass::six' => [
                        realpath(__DIR__ . '/../../_files/source_with_interfaces_classes_traits_functions.php') => range(37, 39),
                    ],
                    'SebastianBergmann\\CodeCoverage\\StaticAnalysis\\ChildClass::one' => [
                        realpath(__DIR__ . '/../../_files/source_with_interfaces_classes_traits_functions.php') => range(41, 43),
                    ],
                    'SebastianBergmann\\CodeCoverage\\StaticAnalysis\\ChildClass::two' => [
                        realpath(__DIR__ . '/../../_files/source_with_interfaces_classes_traits_functions.php') => range(45, 47),
                    ],
                    'SebastianBergmann\\CodeCoverage\\StaticAnalysis\\ChildClass::three' => [
                        realpath(__DIR__ . '/../../_files/source_with_interfaces_classes_traits_functions.php') => range(49, 51),
                    ],
                    'SebastianBergmann\\CodeCoverage\\StaticAnalysis\\T::four' => [
                        realpath(__DIR__ . '/../../_files/source_with_interfaces_classes_traits_functions.php') => range(21, 23),
                    ],
                ],
                'functions' => [
                    'SebastianBergmann\\CodeCoverage\\StaticAnalysis\\f' => [
                        realpath(__DIR__ . '/../../_files/source_with_interfaces_classes_traits_functions.php') => range(54, 56),
                    ],
                ],
            ],
            $this->map([__DIR__ . '/../../_files/source_with_interfaces_classes_traits_functions.php']),
        );
    }

    /**
     * @return array{namespaces: array<non-empty-string, list<positive-int>>, classes: array<non-empty-string, list<positive-int>>, classesThatExtendClass: array<non-empty-string, list<positive-int>>, classesThatImplementInterface: array<non-empty-string, list<positive-int>>, traits: array<non-empty-string, list<positive-int>>, methods: array<non-empty-string, list<positive-int>>, functions: array<non-empty-string, list<positive-int>>}
     */
    private function map(array $files): array
    {
        $filter = new Filter;

        $filter->includeFiles($files);

        return (new MapBuilder)->build($filter, new ParsingFileAnalyser(false, false));
    }
}
