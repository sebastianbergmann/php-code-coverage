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
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProcessedMethodType::class)]
#[Small]
final class ProcessedMethodTypeTest extends TestCase
{
    public function testCanBeConstructed(): void
    {
        $method = new ProcessedMethodType(
            'someMethod',
            'public',
            'someMethod(): void',
            5,
            20,
            10,
            8,
            4,
            3,
            2,
            1,
            2,
            80.0,
            '1.02',
            'SomeClass.php.html#5',
        );

        $this->assertSame('someMethod', $method->methodName);
        $this->assertSame('public', $method->visibility);
        $this->assertSame('someMethod(): void', $method->signature);
        $this->assertSame(5, $method->startLine);
        $this->assertSame(20, $method->endLine);
        $this->assertSame(10, $method->executableLines);
        $this->assertSame(8, $method->executedLines);
        $this->assertSame(4, $method->executableBranches);
        $this->assertSame(3, $method->executedBranches);
        $this->assertSame(2, $method->executablePaths);
        $this->assertSame(1, $method->executedPaths);
        $this->assertSame(2, $method->ccn);
        $this->assertSame(80.0, $method->coverage);
        $this->assertSame('1.02', $method->crap);
        $this->assertSame('SomeClass.php.html#5', $method->link);
    }
}
