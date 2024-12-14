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

/**
 * @phpstan-import-type TargetMap from Mapper
 */
#[CoversClass(MapBuilder::class)]
#[Small]
final class MapBuilderTest extends TestCase
{
    public function testBuildsMap(): void
    {
        $file = realpath(__DIR__ . '/../../_files/source_with_interfaces_classes_traits_functions.php');

        $this->assertSame(
            [
                'namespaces' => [
                    'SebastianBergmann\\CodeCoverage\\StaticAnalysis' => [
                        $file => array_merge(
                            range(26, 31),
                            range(33, 52),
                            range(19, 24),
                            range(54, 56),
                        ),
                    ],
                ],
                'classes' => [
                    'SebastianBergmann\\CodeCoverage\\StaticAnalysis\\ParentClass' => [
                        $file => range(26, 31),
                    ],
                    'SebastianBergmann\\CodeCoverage\\StaticAnalysis\\ChildClass' => [
                        $file => range(33, 52),
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
                'traits' => [
                    'SebastianBergmann\\CodeCoverage\\StaticAnalysis\\T' => [
                        $file => range(19, 24),
                    ],
                ],
                'methods' => [
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
                    'SebastianBergmann\\CodeCoverage\\StaticAnalysis\\T::four' => [
                        $file => range(21, 23),
                    ],
                ],
                'functions' => [
                    'SebastianBergmann\\CodeCoverage\\StaticAnalysis\\f' => [
                        $file => range(54, 56),
                    ],
                ],
                'reverseLookup' => [
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
                    $file . ':21' => 'SebastianBergmann\CodeCoverage\StaticAnalysis\T::four',
                    $file . ':22' => 'SebastianBergmann\CodeCoverage\StaticAnalysis\T::four',
                    $file . ':23' => 'SebastianBergmann\CodeCoverage\StaticAnalysis\T::four',
                    $file . ':54' => 'SebastianBergmann\CodeCoverage\StaticAnalysis\f',
                    $file . ':55' => 'SebastianBergmann\CodeCoverage\StaticAnalysis\f',
                    $file . ':56' => 'SebastianBergmann\CodeCoverage\StaticAnalysis\f',
                ],
            ],
            $this->map([__DIR__ . '/../../_files/source_with_interfaces_classes_traits_functions.php']),
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

        return (new MapBuilder)->build($filter, new ParsingFileAnalyser(false, false));
    }
}
