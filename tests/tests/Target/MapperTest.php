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
use function range;
use function realpath;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\InvalidCodeCoverageTargetException;
use SebastianBergmann\CodeCoverage\StaticAnalysis\ParsingFileAnalyser;

#[CoversClass(Mapper::class)]
#[Small]
final class MapperTest extends TestCase
{
    /**
     * @return non-empty-list<array{0: non-empty-string, 1: array<non-empty-string, non-empty-list<positive-int>>, 2: TargetCollection}>
     */
    public static function provider(): array
    {
        $file = realpath(__DIR__ . '/../../_files/source_with_interfaces_classes_traits_functions.php');

        return [
            [
                'single class',
                [
                    $file => range(33, 52),
                ],
                TargetCollection::fromArray(
                    [
                        Target::forClass('SebastianBergmann\\CodeCoverage\\StaticAnalysis\\ChildClass'),
                    ],
                ),
            ],
        ];
    }

    /**
     * @return non-empty-list<array{0: non-empty-string, 1: non-empty-string, 2: TargetCollection}>
     */
    public static function invalidProvider(): array
    {
        return [
            [
                'single class',
                'Class SebastianBergmann\CodeCoverage\StaticAnalysis\ChildClass is not a valid target for code coverage',
                TargetCollection::fromArray(
                    [
                        Target::forClass('SebastianBergmann\\CodeCoverage\\StaticAnalysis\\ChildClass'),
                    ],
                ),
            ],
        ];
    }

    /**
     * @param array<non-empty-string, non-empty-list<positive-int>> $expected
     */
    #[DataProvider('provider')]
    #[TestDox('Maps TargetCollection with $description to source locations')]
    public function testMapsTargetValueObjectsToSourceLocations(string $description, array $expected, TargetCollection $targets): void
    {
        $this->assertSame(
            $expected,
            $this->mapper(array_keys($expected))->map($targets),
        );
    }

    #[DataProvider('invalidProvider')]
    #[TestDox('Cannot map $description that does not exist to source locations')]
    public function testCannotMapInvalidTargets(string $description, string $exceptionMessage, TargetCollection $targets): void
    {
        $this->expectException(InvalidCodeCoverageTargetException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->mapper([])->map($targets);
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
     * @return array{namespaces: array<non-empty-string, list<positive-int>>, classes: array<non-empty-string, list<positive-int>>, classesThatExtendClass: array<non-empty-string, list<positive-int>>, classesThatImplementInterface: array<non-empty-string, list<positive-int>>, traits: array<non-empty-string, list<positive-int>>, methods: array<non-empty-string, list<positive-int>>, functions: array<non-empty-string, list<positive-int>>}
     */
    private function map(array $files): array
    {
        $filter = new Filter;

        $filter->includeFiles($files);

        return (new MapBuilder)->build($filter, new ParsingFileAnalyser(false, false));
    }
}
