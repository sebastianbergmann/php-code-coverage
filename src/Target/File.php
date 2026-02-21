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
final class File extends Target
{
    /**
     * @var non-empty-string
     */
    private string $path;

    /**
     * @param non-empty-string $path
     */
    protected function __construct(string $path)
    {
        $this->path = $path;
    }

    public function isFile(): true
    {
        return true;
    }

    /**
     * @return non-empty-string
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * @return non-empty-string
     */
    public function key(): string
    {
        return 'files';
    }

    /**
     * @return non-empty-string
     */
    public function target(): string
    {
        return $this->path;
    }

    /**
     * @return non-empty-string
     */
    public function description(): string
    {
        return 'File ' . $this->path;
    }
}
