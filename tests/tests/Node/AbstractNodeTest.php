<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Node;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Class_;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Function_;
use SebastianBergmann\CodeCoverage\StaticAnalysis\LinesOfCode;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Method;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Trait_;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Visibility;

#[CoversClass(AbstractNode::class)]
#[Small]
final class AbstractNodeTest extends TestCase
{
    public function testParentReturnsNullForRoot(): void
    {
        $root = new Directory('root');

        $this->assertNull($root->parent());
    }

    public function testParentReturnsParentNode(): void
    {
        $root  = new Directory('root');
        $child = $root->addDirectory('child');

        $this->assertSame($root, $child->parent());
    }

    public function testPercentageOfTestedClasses(): void
    {
        $file       = $this->createFileWithTestedClass();
        $percentage = $file->percentageOfTestedClasses();

        $this->assertSame('100.00%', $percentage->asString());
    }

    public function testPercentageOfTestedTraits(): void
    {
        $file       = $this->createFileWithTestedTrait();
        $percentage = $file->percentageOfTestedTraits();

        $this->assertIsString($percentage->asString());
    }

    public function testPercentageOfTestedFunctions(): void
    {
        $root = new Directory('root');

        $function = new Function_(
            'myFunc',
            'myFunc',
            '',
            1,
            5,
            'function myFunc(): void',
            1,
        );

        $file = new File(
            'test.php',
            $root,
            'abc123',
            [
                1 => ['test1'],
                2 => ['test1'],
                3 => ['test1'],
                4 => ['test1'],
                5 => ['test1'],
            ],
            [],
            ['test1' => ['size' => 'small', 'status' => 'passed', 'time' => 0.0]],
            [],
            [],
            ['myFunc' => $function],
            new LinesOfCode(5, 0, 5),
        );

        $percentage = $file->percentageOfTestedFunctions();
        $this->assertIsString($percentage->asString());
    }

    public function testPercentageOfTestedMethods(): void
    {
        $file       = $this->createFileWithTestedClass();
        $percentage = $file->percentageOfTestedMethods();

        $this->assertIsString($percentage->asString());
    }

    public function testCyclomaticComplexity(): void
    {
        $file = $this->createFileWithTestedClass();
        $ccn  = $file->cyclomaticComplexity();

        $this->assertGreaterThanOrEqual(0, $ccn);
    }

    public function testCyclomaticComplexityWithFunctions(): void
    {
        $root = new Directory('root');

        $function = new Function_(
            'myFunc',
            'myFunc',
            '',
            1,
            5,
            'function myFunc(): void',
            3,
        );

        $file = new File(
            'test.php',
            $root,
            'abc123',
            [1 => ['test1']],
            [],
            ['test1' => ['size' => 'small', 'status' => 'passed', 'time' => 0.0]],
            [],
            [],
            ['myFunc' => $function],
            new LinesOfCode(5, 0, 5),
        );

        $this->assertGreaterThan(0, $file->cyclomaticComplexity());
    }

    public function testNameStripsTrailingDirectorySeparator(): void
    {
        $root = new Directory('root/');

        $this->assertSame('root', $root->name());
    }

    public function testIdForGrandchild(): void
    {
        $root       = new Directory('root');
        $child      = $root->addDirectory('child');
        $grandchild = $child->addDirectory('grandchild');

        $this->assertSame('child/grandchild', $grandchild->id());
    }

    private function createFileWithTestedClass(): File
    {
        $root = new Directory('root');

        $method = new Method(
            'testMethod',
            1,
            5,
            'public function testMethod(): void',
            Visibility::Public,
            1,
        );

        $class = new Class_(
            'MyClass',
            'MyClass',
            '',
            'test.php',
            1,
            5,
            null,
            [],
            [],
            ['testMethod' => $method],
        );

        return new File(
            'test.php',
            $root,
            'abc123',
            [
                1 => ['test1'],
                2 => ['test1'],
                3 => ['test1'],
                4 => ['test1'],
                5 => ['test1'],
            ],
            [],
            ['test1'   => ['size' => 'small', 'status' => 'passed', 'time' => 0.0]],
            ['MyClass' => $class],
            [],
            [],
            new LinesOfCode(5, 0, 5),
        );
    }

    private function createFileWithTestedTrait(): File
    {
        $root = new Directory('root');

        $method = new Method(
            'traitMethod',
            1,
            5,
            'public function traitMethod(): void',
            Visibility::Public,
            1,
        );

        $trait = new Trait_(
            'MyTrait',
            'MyTrait',
            '',
            'test.php',
            1,
            5,
            [],
            ['traitMethod' => $method],
        );

        return new File(
            'test.php',
            $root,
            'abc123',
            [
                1 => ['test1'],
                2 => ['test1'],
                3 => ['test1'],
                4 => ['test1'],
                5 => ['test1'],
            ],
            [],
            ['test1' => ['size' => 'small', 'status' => 'passed', 'time' => 0.0]],
            [],
            ['MyTrait' => $trait],
            [],
            new LinesOfCode(5, 0, 5),
        );
    }
}
