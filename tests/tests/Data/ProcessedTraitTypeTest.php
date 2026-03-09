<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Data;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProcessedTraitType::class)]
final class ProcessedTraitTypeTest extends TestCase
{
    public function testCanBeConstructed(): void
    {
        $method = new ProcessedMethodType(
            'someMethod',
            'public',
            'someMethod(): void',
            10,
            20,
            5,
            3,
            2,
            1,
            1,
            1,
            1,
            60.0,
            '1.50',
            'SomeTrait.php.html#10',
        );

        $trait = new ProcessedTraitType(
            'SomeTrait',
            'SomeNamespace',
            ['someMethod' => $method],
            5,
            10,
            8,
            4,
            3,
            2,
            1,
            2,
            80.0,
            '1.02',
            'SomeTrait.php.html#5',
        );

        $this->assertSame('SomeTrait', $trait->traitName);
        $this->assertSame('SomeNamespace', $trait->namespace);
        $this->assertCount(1, $trait->methods);
        $this->assertSame(5, $trait->startLine);
        $this->assertSame(10, $trait->executableLines);
        $this->assertSame(8, $trait->executedLines);
        $this->assertSame(4, $trait->executableBranches);
        $this->assertSame(3, $trait->executedBranches);
        $this->assertSame(2, $trait->executablePaths);
        $this->assertSame(1, $trait->executedPaths);
        $this->assertSame(2, $trait->ccn);
        $this->assertSame(80.0, $trait->coverage);
        $this->assertSame('1.02', $trait->crap);
        $this->assertSame('SomeTrait.php.html#5', $trait->link);
    }
}
