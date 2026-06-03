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

#[CoversClass(ProcessedClassType::class)]
#[Small]
final class ProcessedClassTypeTest extends TestCase
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
            'SomeClass.php.html#10',
        );

        $class = new ProcessedClassType(
            'SomeClass',
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
            'SomeClass.php.html#5',
        );

        $this->assertSame('SomeClass', $class->className);
        $this->assertSame('SomeNamespace', $class->namespace);
        $this->assertCount(1, $class->methods);
        $this->assertSame(5, $class->startLine);
        $this->assertSame(10, $class->executableLines);
        $this->assertSame(8, $class->executedLines);
        $this->assertSame(4, $class->executableBranches);
        $this->assertSame(3, $class->executedBranches);
        $this->assertSame(2, $class->executablePaths);
        $this->assertSame(1, $class->executedPaths);
        $this->assertSame(2, $class->ccn);
        $this->assertSame(80.0, $class->coverage);
        $this->assertSame('1.02', $class->crap);
        $this->assertSame('SomeClass.php.html#5', $class->link);
    }
}
