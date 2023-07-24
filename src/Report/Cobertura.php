<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report;

use function dirname;
use function file_put_contents;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\WriteOperationFailedException;
use SebastianBergmann\CodeCoverage\Report\Cobertura\CoberturaCoverage;
use SebastianBergmann\CodeCoverage\Util\Filesystem;

final class Cobertura
{
    /**
     * @throws WriteOperationFailedException
     */
    public function process(CodeCoverage $coverage, ?string $target = null): string
    {
        $buffer = CoberturaCoverage::create($coverage->getReport())->generateDocument()->saveXML();

        if ($target !== null) {
            Filesystem::createDirectory(dirname($target));

            if (@file_put_contents($target, $buffer) === false) {
                throw new WriteOperationFailedException($target);
            }
        }

        return $buffer;
    }
}
