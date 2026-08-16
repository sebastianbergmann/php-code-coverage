<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report\Jsonl;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[CoversClass(RangeEncoder::class)]
#[Small]
final class RangeEncoderTest extends TestCase
{
    /**
     * @return non-empty-array<string, array{list<non-empty-string>, list<positive-int>}>
     */
    public static function provider(): array
    {
        return [
            'empty input'               => [[], []],
            'single line'               => [['77'], [77]],
            'two adjacent lines'        => [['45-46'], [45, 46]],
            'three adjacent lines'      => [['45-47'], [45, 46, 47]],
            'non-adjacent singles'      => [['45', '47', '49'], [45, 47, 49]],
            'run followed by a single'  => [['45-52', '77'], [45, 46, 47, 48, 49, 50, 51, 52, 77]],
            'single followed by a run'  => [['45', '47-49'], [45, 47, 48, 49]],
            'several runs'              => [['45-52', '77', '90-96'], [45, 46, 47, 48, 49, 50, 51, 52, 77, 90, 91, 92, 93, 94, 95, 96]],
            'unsorted input'            => [['45-47', '77'], [77, 46, 45, 47]],
            'duplicated lines'          => [['45-46'], [46, 45, 46, 45]],
            'run separated by one line' => [['45-46', '48-49'], [45, 46, 48, 49]],
        ];
    }

    /**
     * @param list<non-empty-string> $expected
     * @param list<positive-int>     $lines
     */
    #[DataProvider('provider')]
    public function testEncodesLineNumbersAsRanges(array $expected, array $lines): void
    {
        $this->assertSame($expected, RangeEncoder::encode($lines));
    }
}
