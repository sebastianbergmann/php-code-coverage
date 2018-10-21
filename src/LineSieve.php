<?php
/*
 * This file is part of the php-code-coverage package.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage;

interface LineSieve
{
    public function setCacheTokens(bool $flag): void;

    public function getCacheTokens(): bool;

    public function setDisableIgnoredLines(bool $flag): void;

    public function setIgnoreDeprecatedCode(bool $flag): void;

    /**
     * @return int[]
     */
    public function getLinesToBeIgnored(string $fileName): array;
}
