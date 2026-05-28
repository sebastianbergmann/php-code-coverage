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

/**
 * @immutable
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class DirectoryRecursively extends Target
{
    /**
     * @var non-empty-string
     */
    private string $directory;

    /**
     * @param non-empty-string $directory
     */
    protected function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    public function isDirectoryRecursively(): true
    {
        return true;
    }

    /**
     * @return non-empty-string
     */
    public function directory(): string
    {
        return $this->directory;
    }

    public function key(): string
    {
        return 'directoriesRecursively';
    }

    /**
     * @return non-empty-string
     */
    public function target(): string
    {
        return $this->directory;
    }

    /**
     * @return non-empty-string
     */
    public function description(): string
    {
        return 'Directory (recursively) ' . $this->target();
    }
}
