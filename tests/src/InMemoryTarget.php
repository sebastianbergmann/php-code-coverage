<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage;

use function array_key_exists;
use function in_array;
use function stream_get_wrappers;
use function stream_wrapper_register;
use function strlen;

/**
 * In-memory stream wrapper used to capture report output without writing to the
 * real filesystem. The report writers delegate to Util\Filesystem::write(),
 * which honours stream wrappers, so a "mem://" target keeps everything in memory.
 */
final class InMemoryTarget
{
    /**
     * @var array<string, string>
     */
    private static array $files = [];

    /**
     * @var resource
     */
    public $context;
    private string $path = '';

    /**
     * @param non-empty-string $name
     *
     * @return non-empty-string
     */
    public static function target(string $name): string
    {
        if (!in_array('mem', stream_get_wrappers(), true)) {
            stream_wrapper_register('mem', self::class);
        }

        self::$files = [];

        return 'mem://' . $name;
    }

    public static function content(string $target): string
    {
        return self::$files[$target] ?? '';
    }

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        $this->path         = $path;
        self::$files[$path] = '';

        return true;
    }

    public function stream_write(string $data): int
    {
        self::$files[$this->path] .= $data;

        return strlen($data);
    }

    public function stream_eof(): bool
    {
        return true;
    }

    public function stream_flush(): bool
    {
        return true;
    }

    public function stream_close(): void
    {
    }

    /**
     * @return array<int|string, int>
     */
    public function url_stat(string $path, int $flags): array
    {
        return array_key_exists($path, self::$files) ? ['size' => strlen(self::$files[$path])] : [];
    }
}
