<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Driver;

use const DIRECTORY_SEPARATOR;
use function str_replace;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[CoversClass(PathPrefixBuilder::class)]
#[Small]
final class PathPrefixBuilderTest extends TestCase
{
    /**
     * @return non-empty-array<non-empty-string, array{list<non-empty-string>, list<non-empty-string>, list<non-empty-string>}>
     */
    public static function provider(): array
    {
        return [
            'no files' => [
                [],
                [],
                [],
            ],
            'files in a directory' => [
                [
                    '/project/src/',
                ],
                [
                    '/project/src/a.php',
                    '/project/src/b.php',
                ],
                [],
            ],
            'files in a directory and in its subdirectories' => [
                [
                    '/project/src/',
                ],
                [
                    '/project/src/Foo/Bar/c.php',
                    '/project/src/a.php',
                    '/project/src/Foo/b.php',
                ],
                [],
            ],
            'files in subdirectories of a directory, but not in that directory' => [
                [
                    '/project/src/Bar/',
                    '/project/src/Foo/',
                ],
                [
                    '/project/src/Foo/a.php',
                    '/project/src/Bar/b.php',
                    '/project/src/Bar/Baz/c.php',
                ],
                [],
            ],
            'files in directories whose names start the same way' => [
                [
                    '/project/src-old/',
                    '/project/src/',
                ],
                [
                    '/project/src/a.php',
                    '/project/src-old/b.php',
                ],
                [],
            ],
            'file in the root directory' => [
                [
                    '/',
                ],
                [
                    '/a.php',
                    '/project/b.php',
                ],
                [],
            ],
            'file that must not be matched is in the directory' => [
                [
                    '/project/a.php',
                    '/project/b.php',
                    '/project/src/',
                ],
                [
                    '/project/a.php',
                    '/project/src/c.php',
                    '/project/b.php',
                    '/project/src/Foo/d.php',
                ],
                [
                    '/project/bootstrap.php',
                ],
            ],
            'file that must not be matched is in a subdirectory of the directory' => [
                [
                    '/project/a.php',
                    '/project/src/',
                ],
                [
                    '/project/a.php',
                    '/project/src/b.php',
                ],
                [
                    '/project/vendor/package/src/c.php',
                ],
            ],
            'file that must not be matched is in a directory that is in a directory that is otherwise used' => [
                [
                    '/project/src/a.php',
                    '/project/src/Bar/',
                    '/project/src/Foo/b.php',
                    '/project/src/Foo/Baz/',
                ],
                [
                    '/project/src/a.php',
                    '/project/src/Foo/b.php',
                    '/project/src/Foo/Baz/c.php',
                    '/project/src/Bar/d.php',
                ],
                [
                    '/project/src/Foo/generated.php',
                ],
            ],
            'file that must not be matched is not in a directory that is used' => [
                [
                    '/project/src/',
                ],
                [
                    '/project/src/a.php',
                ],
                [
                    '/project/vendor/autoload.php',
                    '/project/src-old/b.php',
                ],
            ],
            'file that must not be matched is a file to collect code coverage data for' => [
                [
                    '/project/src/',
                ],
                [
                    '/project/src/a.php',
                    '/project/src/b.php',
                ],
                [
                    '/project/src/a.php',
                ],
            ],
        ];
    }

    /**
     * @param list<non-empty-string> $expected
     * @param list<non-empty-string> $files
     * @param list<non-empty-string> $filesThatMustNotBeMatched
     */
    #[DataProvider('provider')]
    public function testBuildsPathPrefixes(array $expected, array $files, array $filesThatMustNotBeMatched): void
    {
        $this->assertSame(
            self::paths($expected),
            (new PathPrefixBuilder)->build(
                self::paths($files),
                self::paths($filesThatMustNotBeMatched),
            ),
        );
    }

    public function testIgnoresEmptyStringInFilesThatMustNotBeMatched(): void
    {
        $this->assertSame(
            self::paths(['/project/src/']),
            (new PathPrefixBuilder)->build(
                self::paths(['/project/src/a.php']),
                [''],
            ),
        );
    }

    /**
     * @param list<non-empty-string> $paths
     *
     * @return list<non-empty-string>
     */
    private static function paths(array $paths): array
    {
        $result = [];

        foreach ($paths as $path) {
            $result[] = str_replace('/', DIRECTORY_SEPARATOR, $path);
        }

        return $result;
    }
}
