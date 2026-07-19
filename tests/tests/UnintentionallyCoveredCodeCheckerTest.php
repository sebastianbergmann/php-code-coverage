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
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\Data\RawCodeCoverageData;
use SebastianBergmann\CodeCoverage\Test\Target\Mapper;
use SebastianBergmann\CodeCoverage\Test\Target\Target;
use SebastianBergmann\CodeCoverage\Test\Target\TargetCollection;

/**
 * @phpstan-import-type TargetMap from Mapper
 */
#[CoversClass(UnintentionallyCoveredCodeChecker::class)]
#[Small]
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

    public function testProcessReducesUnitToClassNameByDefault(): void
    {
        $this->assertSame(
            ['CoveredClass'],
            $this->processor->process(['CoveredClass::publicMethod'], []),
        );
    }

    public function testProcessKeepsFullMethodNameWhenMethodLevelReportingIsEnabled(): void
    {
        $this->assertSame(
            ['CoveredClass::publicMethod'],
            $this->processor->process(['CoveredClass::publicMethod'], [], true),
        );
    }

    public function testProcessDeduplicatesMethodsToClassNameByDefault(): void
    {
        $this->assertSame(
            ['CoveredClass'],
            $this->processor->process([
                'CoveredClass::publicMethod',
                'CoveredClass::protectedMethod',
            ], []),
        );
    }

    public function testProcessKeepsDistinctMethodsWhenMethodLevelReportingIsEnabled(): void
    {
        $this->assertSame(
            ['CoveredClass::protectedMethod', 'CoveredClass::publicMethod'],
            $this->processor->process([
                'CoveredClass::publicMethod',
                'CoveredClass::protectedMethod',
            ], [], true),
        );
    }

    public function testProcessRemovesDuplicateInputUnits(): void
    {
        $this->assertSame(
            ['CoveredClass::publicMethod'],
            $this->processor->process([
                'CoveredClass::publicMethod',
                'CoveredClass::publicMethod',
            ], [], true),
        );
    }

    public function testProcessSortsResultAtMethodLevel(): void
    {
        $this->assertSame(
            ['CoveredClass::publicMethod', 'CoveredParentClass::publicMethod'],
            $this->processor->process([
                'CoveredParentClass::publicMethod',
                'CoveredClass::publicMethod',
            ], [], true),
        );
    }

    public function testProcessSortsResultAtClassLevel(): void
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

    public function testProcessDoesNotFilterClassThatIsNotSubclassOfExcludedParentWithMethodLevelReporting(): void
    {
        $this->assertSame(
            ['CoveredParentClass::publicMethod'],
            $this->processor->process(
                ['CoveredParentClass::publicMethod'],
                ['CoveredParentClass'],
                true,
            ),
        );
    }

    public function testProcessThrowsReflectionExceptionForNonExistentClass(): void
    {
        $this->expectException(ReflectionException::class);

        $this->processor->process(['NonExistentClass::method'], []);
    }

    public function testProcessHandlesMixedUnitsAtClassLevel(): void
    {
        $this->assertSame(
            ['CoveredClass', 'some_function'],
            $this->processor->process([
                'some_function',
                'CoveredClass::publicMethod',
            ], []),
        );
    }

    public function testProcessHandlesMixedUnitsAtMethodLevel(): void
    {
        $this->assertSame(
            ['CoveredClass::publicMethod', 'some_function'],
            $this->processor->process([
                'some_function',
                'CoveredClass::publicMethod',
            ], [], true),
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

        $this->assertArrayHasKey('file.php', $result);
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

        $this->assertArrayHasKey('file.php', $result);
        $this->assertArrayHasKey(5, $result['file.php']);
    }

    public function testCheckDoesNotThrowWhenAllCoveredLinesAreAllowed(): void
    {
        $data = RawCodeCoverageData::fromLineCoverage([
            'file.php' => [1 => 1, 2 => 1],
        ]);

        $mapper = new Mapper($this->emptyMap());

        $result = $this->processor->check(
            $data,
            ['file.php' => [1, 2]],
            [],
            $mapper,
            [],
            $this->emptyTargetCollection(),
            $this->emptyTargetCollection(),
        );

        $this->assertTrue($result);
    }

    public function testCheckThrowsWhenUnintentionallyCoveredCodeIsDetected(): void
    {
        $data = RawCodeCoverageData::fromLineCoverage([
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
            $this->emptyTargetCollection(),
            $this->emptyTargetCollection(),
        );
    }

    public function testCheckDoesNotThrowWhenCoveredLineIsInLinesToBeUsed(): void
    {
        $data = RawCodeCoverageData::fromLineCoverage([
            'file.php' => [1 => 1, 2 => 1],
        ]);

        $mapper = new Mapper($this->emptyMap());

        $result = $this->processor->check(
            $data,
            ['file.php' => [1]],
            ['file.php' => [2]],
            $mapper,
            [],
            $this->emptyTargetCollection(),
            $this->emptyTargetCollection(),
        );

        $this->assertTrue($result);
    }

    public function testCheckIgnoresLinesWithFlagOtherThanOne(): void
    {
        $data = RawCodeCoverageData::fromLineCoverage([
            'file.php' => [1 => 1, 2 => -1, 3 => -2],
        ]);

        $mapper = new Mapper($this->emptyMap());

        $result = $this->processor->check(
            $data,
            ['file.php' => [1]],
            [],
            $mapper,
            [],
            $this->emptyTargetCollection(),
            $this->emptyTargetCollection(),
        );

        $this->assertTrue($result);
    }

    public function testCheckDoesNotThrowWhenNoCoverageData(): void
    {
        $data = RawCodeCoverageData::fromLineCoverage([]);

        $mapper = new Mapper($this->emptyMap());

        $result = $this->processor->check(
            $data,
            ['file.php' => [1]],
            [],
            $mapper,
            [],
            $this->emptyTargetCollection(),
            $this->emptyTargetCollection(),
        );

        $this->assertTrue($result);
    }

    public function testCheckReportsMethodLevelWhenMethodIsTargeted(): void
    {
        $data = RawCodeCoverageData::fromLineCoverage([
            'file.php' => [1 => 1, 5 => 1],
        ]);

        $map                                = $this->emptyMap();
        $map['reverseLookup']['file.php:5'] = 'CoveredClass::publicMethod';

        $mapper = new Mapper($map);

        $covers = TargetCollection::fromArray([
            Target::forMethod('CoveredClass', 'protectedMethod'),
        ]);

        try {
            $this->processor->check(
                $data,
                ['file.php' => [1]],
                [],
                $mapper,
                [],
                $covers,
                $this->emptyTargetCollection(),
            );

            $this->fail('Expected UnintentionallyCoveredCodeException');
        } catch (UnintentionallyCoveredCodeException $e) {
            $this->assertSame(['CoveredClass::publicMethod'], $e->getUnintentionallyCoveredUnits());
        }
    }

    public function testCheckReportsClassLevelWhenClassIsTargeted(): void
    {
        $data = RawCodeCoverageData::fromLineCoverage([
            'file.php' => [1 => 1, 5 => 1],
        ]);

        $map                                = $this->emptyMap();
        $map['reverseLookup']['file.php:5'] = 'CoveredClass::publicMethod';

        $mapper = new Mapper($map);

        $covers = TargetCollection::fromArray([
            Target::forClass('CoveredClass'),
        ]);

        try {
            $this->processor->check(
                $data,
                ['file.php' => [1]],
                [],
                $mapper,
                [],
                $covers,
                $this->emptyTargetCollection(),
            );

            $this->fail('Expected UnintentionallyCoveredCodeException');
        } catch (UnintentionallyCoveredCodeException $e) {
            $this->assertSame(['CoveredClass'], $e->getUnintentionallyCoveredUnits());
        }
    }

    public function testCheckExcludesSubclassesOfExcludedParent(): void
    {
        $data = RawCodeCoverageData::fromLineCoverage([
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
            $this->emptyTargetCollection(),
            $this->emptyTargetCollection(),
        );

        $this->assertTrue($result);
    }

    public function testCheckHandlesMultipleFiles(): void
    {
        $data = RawCodeCoverageData::fromLineCoverage([
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
            $this->emptyTargetCollection(),
            $this->emptyTargetCollection(),
        );

        $this->assertTrue($result);
    }

    public function testCheckReportsMethodLevelWhenAnyMethodTargetIsPresent(): void
    {
        $data = RawCodeCoverageData::fromLineCoverage([
            'file.php' => [1 => 1, 5 => 1, 10 => 1],
        ]);

        $map                                 = $this->emptyMap();
        $map['reverseLookup']['file.php:5']  = 'CoveredClass::publicMethod';
        $map['reverseLookup']['file.php:10'] = 'CoveredParentClass::protectedMethod';

        $mapper = new Mapper($map);

        $covers = TargetCollection::fromArray([
            Target::forClass('CoveredClass'),
            Target::forMethod('CoveredParentClass', 'publicMethod'),
        ]);

        try {
            $this->processor->check(
                $data,
                ['file.php' => [1]],
                [],
                $mapper,
                [],
                $covers,
                $this->emptyTargetCollection(),
            );

            $this->fail('Expected UnintentionallyCoveredCodeException');
        } catch (UnintentionallyCoveredCodeException $e) {
            $this->assertSame(
                ['CoveredClass::publicMethod', 'CoveredParentClass::protectedMethod'],
                $e->getUnintentionallyCoveredUnits(),
            );
        }
    }

    public function testCheckReportsClassLevelWhenTraitIsTargeted(): void
    {
        $data = RawCodeCoverageData::fromLineCoverage([
            'file.php' => [1 => 1, 5 => 1],
        ]);

        $map                                = $this->emptyMap();
        $map['reverseLookup']['file.php:5'] = 'SebastianBergmann\CodeCoverage\TestFixture\Target\TraitOne::one';

        $mapper = new Mapper($map);

        $covers = TargetCollection::fromArray([
            Target::forTrait('SebastianBergmann\CodeCoverage\TestFixture\Target\TraitOne'),
        ]);

        try {
            $this->processor->check(
                $data,
                ['file.php' => [1]],
                [],
                $mapper,
                [],
                $covers,
                $this->emptyTargetCollection(),
            );

            $this->fail('Expected UnintentionallyCoveredCodeException');
        } catch (UnintentionallyCoveredCodeException $e) {
            $this->assertSame(
                ['SebastianBergmann\CodeCoverage\TestFixture\Target\TraitOne'],
                $e->getUnintentionallyCoveredUnits(),
            );
        }
    }

    public function testCheckUsesClassLevelTargetsFromUsesCollection(): void
    {
        $data = RawCodeCoverageData::fromLineCoverage([
            'file.php' => [1 => 1, 5 => 1],
        ]);

        $map                                = $this->emptyMap();
        $map['reverseLookup']['file.php:5'] = 'CoveredClass::publicMethod';

        $mapper = new Mapper($map);

        $uses = TargetCollection::fromArray([
            Target::forClass('CoveredClass'),
        ]);

        try {
            $this->processor->check(
                $data,
                ['file.php' => [1]],
                [],
                $mapper,
                [],
                $this->emptyTargetCollection(),
                $uses,
            );

            $this->fail('Expected UnintentionallyCoveredCodeException');
        } catch (UnintentionallyCoveredCodeException $e) {
            $this->assertSame(['CoveredClass'], $e->getUnintentionallyCoveredUnits());
        }
    }

    /**
     * @return TargetMap
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
            'files'                         => [],
            'directories'                   => [],
            'directoriesRecursively'        => [],
            'reverseLookup'                 => [],
        ];
    }

    private function emptyTargetCollection(): TargetCollection
    {
        return TargetCollection::fromArray([]);
    }
}
