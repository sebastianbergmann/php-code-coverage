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
use SebastianBergmann\CodeCoverage\StaticAnalysis\Function_;
use SebastianBergmann\CodeCoverage\StaticAnalysis\LinesOfCode;

#[CoversClass(Directory::class)]
#[Small]
final class DirectoryTest extends TestCase
{
    public function testCountWithNoChildren(): void
    {
        $root = new Directory('root');

        $this->assertSame(0, $root->count());
    }

    public function testCountWithFiles(): void
    {
        $root = new Directory('root');

        $root->addFile($this->createFile($root, 'a.php'));
        $root->addFile($this->createFile($root, 'b.php'));

        $this->assertSame(2, $root->count());
    }

    public function testCountWithNestedDirectories(): void
    {
        $root  = new Directory('root');
        $child = $root->addDirectory('sub');

        $child->addFile($this->createFile($child, 'a.php'));

        $this->assertSame(1, $root->count());
    }

    public function testAddDirectoryReturnsNewDirectory(): void
    {
        $root  = new Directory('root');
        $child = $root->addDirectory('sub');

        $this->assertSame('sub', $child->name());
        $this->assertCount(1, $root->directories());
        $this->assertCount(1, $root->children());
    }

    public function testFunctionsAggregatesFromChildren(): void
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
            [1 => ['test1']],
            [],
            ['test1' => ['size' => 'small', 'status' => 'passed', 'time' => 0.0]],
            [],
            [],
            ['myFunc' => $function],
            new LinesOfCode(5, 0, 5),
        );

        $root->addFile($file);

        $this->assertArrayHasKey('myFunc', $root->functions());
    }

    public function testFunctionsAreCached(): void
    {
        $root = new Directory('root');

        $first  = $root->functions();
        $second = $root->functions();

        $this->assertSame($first, $second);
    }

    private function createFile(Directory $parent, string $name): File
    {
        return new File(
            $name,
            $parent,
            'sha1hash',
            [],
            [],
            [],
            [],
            [],
            [],
            new LinesOfCode(0, 0, 0),
        );
    }
}
