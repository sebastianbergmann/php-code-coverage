<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage;

use function array_keys;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\Data\RawCodeCoverageData;
use SebastianBergmann\CodeCoverage\Test\Target\Mapper;

#[CoversClass(UnintentionallyCoveredCodeChecker::class)]
final class UnintentionallyCoveredCodeCheckerTest extends TestCase
{
    private UnintentionallyCoveredCodeChecker $processor;

    protected function setUp(): void
    {
        $this->processor = new UnintentionallyCoveredCodeChecker;
    }

    public function testProcessWithEmptyInputReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->processor->process([], []));
    }

    public function testProcessKeepsUnitWithoutDoubleColonAsIs(): void
    {
        $this->assertSame(
            ['some_function'],
            $this->processor->process(['some_function'], []),
        );
    }

    public function testProcessReducesUnitWithDoubleColonToClassName(): void
    {
        $this->assertSame(
            ['CoveredClass'],
            $this->processor->process(['CoveredClass::publicMethod'], []),
        );
    }

    public function testProcessRemovesDuplicateClassNames(): void
    {
        $this->assertSame(
            ['CoveredClass'],
            $this->processor->process([
                'CoveredClass::publicMethod',
                'CoveredClass::protectedMethod',
            ], []),
        );
    }

    public function testProcessRemovesDuplicateInputUnits(): void
    {
        $this->assertSame(
            ['CoveredClass'],
            $this->processor->process([
                'CoveredClass::publicMethod',
                'CoveredClass::publicMethod',
            ], []),
        );
    }

    public function testProcessSortsResult(): void
    {
        $this->assertSame(
            ['CoveredClass', 'CoveredParentClass'],
            $this->processor->process([
                'CoveredParentClass::publicMethod',
                'CoveredClass::publicMethod',
            ], []),
        );
    }

    public function testProcessFiltersSubclassOfExcludedParent(): void
    {
        $this->assertSame(
            [],
            $this->processor->process(
                ['CoveredClass::publicMethod'],
                ['CoveredParentClass'],
            ),
        );
    }

    public function testProcessDoesNotFilterClassThatIsNotSubclassOfExcludedParent(): void
    {
        $this->assertSame(
            ['CoveredParentClass'],
            $this->processor->process(
                ['CoveredParentClass::publicMethod'],
                ['CoveredParentClass'],
            ),
        );
    }

    public function testProcessThrowsReflectionExceptionForNonExistentClass(): void
    {
        $this->expectException(ReflectionException::class);

        $this->processor->process(['NonExistentClass::method'], []);
    }

    public function testProcessHandlesMixedUnits(): void
    {
        $this->assertSame(
            ['CoveredClass', 'some_function'],
            $this->processor->process([
                'some_function',
                'CoveredClass::publicMethod',
            ], []),
        );
    }

    public function testProcessExcludedSubclassDoesNotAffectFunctionUnits(): void
    {
        $this->assertSame(
            ['some_function'],
            $this->processor->process(
                [
                    'CoveredClass::publicMethod',
                    'some_function',
                ],
                ['CoveredParentClass'],
            ),
        );
    }

    public function testGetAllowedLinesWithEmptyInputReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->processor->allowedLines([], []));
    }

    public function testGetAllowedLinesWithOnlyLinesToBeCovered(): void
    {
        $result = $this->processor->allowedLines(
            ['file.php' => [1, 2, 3]],
            [],
        );

        $this->assertSame(
            ['file.php' => [1 => 0, 2 => 1, 3 => 2]],
            $result,
        );
    }

    public function testGetAllowedLinesWithOnlyLinesToBeUsed(): void
    {
        $result = $this->processor->allowedLines(
            [],
            ['file.php' => [10, 20]],
        );

        $this->assertSame(
            ['file.php' => [10 => 0, 20 => 1]],
            $result,
        );
    }

    public function testGetAllowedLinesMergesCoveredAndUsedLines(): void
    {
        $result = $this->processor->allowedLines(
            ['file.php' => [1, 2]],
            ['file.php' => [3, 4]],
        );

        $this->assertSame(
            ['file.php' => [1 => 0, 2 => 1, 3 => 2, 4 => 3]],
            $result,
        );
    }

    public function testGetAllowedLinesRemovesDuplicateLines(): void
    {
        $result = $this->processor->allowedLines(
            ['file.php' => [1, 2]],
            ['file.php' => [2, 3]],
        );

        $this->assertSame([1, 2, 3], array_keys($result['file.php']));
    }

    public function testGetAllowedLinesHandlesMultipleFiles(): void
    {
        $result = $this->processor->allowedLines(
            ['a.php' => [1], 'b.php' => [2]],
            ['b.php' => [3], 'c.php' => [4]],
        );

        $this->assertSame(
            [
                'a.php' => [1 => 0],
                'b.php' => [2 => 0, 3 => 1],
                'c.php' => [4 => 0],
            ],
            $result,
        );
    }

    public function testGetAllowedLinesFlipsLineNumbersToKeys(): void
    {
        $result = $this->processor->allowedLines(
            ['file.php' => [5]],
            [],
        );

        $this->assertArrayHasKey(5, $result['file.php']);
    }

    public function testCheckDoesNotThrowWhenAllCoveredLinesAreAllowed(): void
    {
        $data = RawCodeCoverageData::fromXdebugWithoutPathCoverage([
            'file.php' => [1 => 1, 2 => 1],
        ]);

        $mapper = new Mapper($this->emptyMap());

        $result = $this->processor->check(
            $data,
            ['file.php' => [1, 2]],
            [],
            $mapper,
            [],
        );

        $this->assertTrue($result);
    }

    public function testCheckThrowsWhenUnintentionallyCoveredCodeIsDetected(): void
    {
        $data = RawCodeCoverageData::fromXdebugWithoutPathCoverage([
            'file.php' => [1 => 1, 2 => 1],
        ]);

        $mapper = new Mapper($this->emptyMap());

        $this->expectException(UnintentionallyCoveredCodeException::class);

        $this->processor->check(
            $data,
            ['file.php' => [1]],
            [],
            $mapper,
            [],
        );
    }

    public function testCheckDoesNotThrowWhenCoveredLineIsInLinesToBeUsed(): void
    {
        $data = RawCodeCoverageData::fromXdebugWithoutPathCoverage([
            'file.php' => [1 => 1, 2 => 1],
        ]);

        $mapper = new Mapper($this->emptyMap());

        $result = $this->processor->check(
            $data,
            ['file.php' => [1]],
            ['file.php' => [2]],
            $mapper,
            [],
        );

        $this->assertTrue($result);
    }

    public function testCheckIgnoresLinesWithFlagOtherThanOne(): void
    {
        $data = RawCodeCoverageData::fromXdebugWithoutPathCoverage([
            'file.php' => [1 => 1, 2 => -1, 3 => -2],
        ]);

        $mapper = new Mapper($this->emptyMap());

        $result = $this->processor->check(
            $data,
            ['file.php' => [1]],
            [],
            $mapper,
            [],
        );

        $this->assertTrue($result);
    }

    public function testCheckDoesNotThrowWhenNoCoverageData(): void
    {
        $data = RawCodeCoverageData::fromXdebugWithoutPathCoverage([]);

        $mapper = new Mapper($this->emptyMap());

        $result = $this->processor->check(
            $data,
            ['file.php' => [1]],
            [],
            $mapper,
            [],
        );

        $this->assertTrue($result);
    }

    public function testCheckUsesMapperForReverseLookup(): void
    {
        $data = RawCodeCoverageData::fromXdebugWithoutPathCoverage([
            'file.php' => [1 => 1, 5 => 1],
        ]);

        $map                                = $this->emptyMap();
        $map['reverseLookup']['file.php:5'] = 'CoveredClass::publicMethod';

        $mapper = new Mapper($map);

        try {
            $this->processor->check(
                $data,
                ['file.php' => [1]],
                [],
                $mapper,
                [],
            );

            $this->fail('Expected UnintentionallyCoveredCodeException');
        } catch (UnintentionallyCoveredCodeException $e) {
            $this->assertSame(['CoveredClass'], $e->getUnintentionallyCoveredUnits());
        }
    }

    public function testCheckExcludesSubclassesOfExcludedParent(): void
    {
        $data = RawCodeCoverageData::fromXdebugWithoutPathCoverage([
            'file.php' => [1 => 1, 5 => 1],
        ]);

        $map                                = $this->emptyMap();
        $map['reverseLookup']['file.php:5'] = 'CoveredClass::publicMethod';

        $mapper = new Mapper($map);

        $result = $this->processor->check(
            $data,
            ['file.php' => [1]],
            [],
            $mapper,
            ['CoveredParentClass'],
        );

        $this->assertTrue($result);
    }

    public function testCheckHandlesMultipleFiles(): void
    {
        $data = RawCodeCoverageData::fromXdebugWithoutPathCoverage([
            'a.php' => [1 => 1],
            'b.php' => [2 => 1],
        ]);

        $mapper = new Mapper($this->emptyMap());

        $result = $this->processor->check(
            $data,
            ['a.php' => [1], 'b.php' => [2]],
            [],
            $mapper,
            [],
        );

        $this->assertTrue($result);
    }

    /**
     * @return array{namespaces: array<empty>, traits: array<empty>, classes: array<empty>, classesThatExtendClass: array<empty>, classesThatImplementInterface: array<empty>, methods: array<empty>, functions: array<empty>, reverseLookup: array<empty>}
     */
    private function emptyMap(): array
    {
        return [
            'namespaces'                    => [],
            'traits'                        => [],
            'classes'                       => [],
            'classesThatExtendClass'        => [],
            'classesThatImplementInterface' => [],
            'methods'                       => [],
            'functions'                     => [],
            'reverseLookup'                 => [],
        ];
    }
}
