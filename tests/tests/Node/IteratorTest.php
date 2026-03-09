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
use SebastianBergmann\CodeCoverage\StaticAnalysis\LinesOfCode;

#[CoversClass(Iterator::class)]
#[Small]
final class IteratorTest extends TestCase
{
    public function testKeyReturnsCurrentPosition(): void
    {
        $root = new Directory('root');
        $root->addFile($this->createFile($root, 'a.php'));

        $iterator = new Iterator($root);
        $iterator->rewind();

        $this->assertSame(0, $iterator->key());
    }

    public function testGetChildrenReturnsIteratorForDirectory(): void
    {
        $root = new Directory('root');
        $root->addDirectory('sub');

        $iterator = new Iterator($root);
        $iterator->rewind();

        $this->assertTrue($iterator->hasChildren());
    }

    public function testFullIteration(): void
    {
        $root = new Directory('root');
        $root->addFile($this->createFile($root, 'a.php'));
        $root->addFile($this->createFile($root, 'b.php'));

        $iterator = new Iterator($root);
        $iterator->rewind();

        $this->assertTrue($iterator->valid());
        $this->assertSame(0, $iterator->key());
        $this->assertNotNull($iterator->current());

        $iterator->next();

        $this->assertTrue($iterator->valid());
        $this->assertSame(1, $iterator->key());

        $iterator->next();

        $this->assertFalse($iterator->valid());
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
