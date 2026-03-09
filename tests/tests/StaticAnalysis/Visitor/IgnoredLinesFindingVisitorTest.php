<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\StaticAnalysis;

use function assert;
use function file_get_contents;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(IgnoredLinesFindingVisitor::class)]
final class IgnoredLinesFindingVisitorTest extends TestCase
{
    public function testAnonymousClassIsNotProcessed(): void
    {
        $ignoredLines = $this->findIgnoredLines(
            TEST_FILES_PATH . 'source_with_anonymous_class.php',
            true,
            false,
        );

        // Anonymous class start line should not be in ignored lines
        // (only named classes get their start line added)
        $this->assertNotContains(4, $ignoredLines);
    }

    public function testDeprecatedMethodIsIgnoredWhenIgnoreDeprecatedIsTrue(): void
    {
        $ignoredLines = $this->findIgnoredLines(
            TEST_FILES_PATH . 'source_with_deprecated_method.php',
            true,
            true,
        );

        // Lines 9-11 cover the deprecated method
        $this->assertContains(9, $ignoredLines);
        $this->assertContains(10, $ignoredLines);
        $this->assertContains(11, $ignoredLines);
    }

    public function testDeprecatedMethodIsNotIgnoredWhenIgnoreDeprecatedIsFalse(): void
    {
        $ignoredLines = $this->findIgnoredLines(
            TEST_FILES_PATH . 'source_with_deprecated_method.php',
            true,
            false,
        );

        // The method body lines should not be in ignored lines
        $this->assertNotContains(10, $ignoredLines);
        $this->assertNotContains(11, $ignoredLines);
        $this->assertNotContains(12, $ignoredLines);
    }

    /**
     * @return list<int>
     */
    private function findIgnoredLines(string $filename, bool $useAnnotationsForIgnoringCode, bool $ignoreDeprecatedCode): array
    {
        $source = file_get_contents($filename);
        $nodes  = (new ParserFactory)->createForHostVersion()->parse($source);

        assert($nodes !== null);

        $traverser                  = new NodeTraverser;
        $ignoredLinesFindingVisitor = new IgnoredLinesFindingVisitor($useAnnotationsForIgnoringCode, $ignoreDeprecatedCode);

        $traverser->addVisitor(new NameResolver);
        $traverser->addVisitor(new AttributeParentConnectingVisitor);
        $traverser->addVisitor($ignoredLinesFindingVisitor);
        $traverser->traverse($nodes);

        return $ignoredLinesFindingVisitor->ignoredLines();
    }
}
