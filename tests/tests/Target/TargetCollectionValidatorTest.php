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

use function assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\StaticAnalysis\ParsingFileAnalyser;
use SebastianBergmann\CodeCoverage\TestFixture\Target\TargetClass;

/**
 * @phpstan-import-type TargetMap from Mapper
 */
#[CoversClass(TargetCollectionValidator::class)]
#[Small]
final class TargetCollectionValidatorTest extends TestCase
{
    public function test_TargetCollection_is_valid_when_all_targets_can_be_mapped(): void
    {
        $targets   = TargetCollection::fromArray([Target::forClass(TargetClass::class)]);
        $mapper    = $this->mapper([__DIR__ . '/../../_files/Target/TargetClass.php']);
        $validator = new TargetCollectionValidator;

        $result = $validator->validate($mapper, $targets);

        $this->assertTrue($result->isSuccess());
    }

    public function test_TargetCollection_is_invalid_when_target_cannot_be_mapped(): void
    {
        $targets   = TargetCollection::fromArray([Target::forClass(TargetClass::class)]);
        $mapper    = $this->mapper([]);
        $validator = new TargetCollectionValidator;

        $result = $validator->validate($mapper, $targets);

        $this->assertTrue($result->isFailure());

        assert($result instanceof ValidationFailure);

        $this->assertSame(
            'Class SebastianBergmann\CodeCoverage\TestFixture\Target\TargetClass is not a valid target for code coverage',
            $result->message(),
        );
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
