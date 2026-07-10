<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report\Html;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
enum Views
{
    public function fileView(): bool
    {
        return $this !== self::OnlyClassView;
    }

    public function classView(): bool
    {
        return $this !== self::OnlyFileView;
    }

    case FileViewAndClassView;
    case OnlyFileView;
    case OnlyClassView;
}
