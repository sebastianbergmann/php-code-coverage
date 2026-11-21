<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report\Xml;

use SebastianBergmann\CodeCoverage\Util\EnsuresUtf8;
use XMLWriter;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final readonly class Method
{
    use EnsuresUtf8;
    private XMLWriter $xmlWriter;

    public function __construct(
        XMLWriter $xmlWriter,
        string $name,
        string $signature,
        string $start,
        ?string $end,
        string $executable,
        string $executed,
        string $coverage,
        string $crap,
        int $executableBranches,
        int $executedBranches,
        int $executablePaths,
        int $executedPaths,
    ) {
        $this->xmlWriter = $xmlWriter;

        $this->xmlWriter->writeAttribute('name', $this->ensureUtf8($name));
        $this->xmlWriter->writeAttribute('signature', $this->ensureUtf8($signature));

        $this->xmlWriter->writeAttribute('start', $start);

        if ($end !== null) {
            $this->xmlWriter->writeAttribute('end', $end);
        }

        $this->xmlWriter->writeAttribute('crap', $crap);

        $this->xmlWriter->writeAttribute('executable', $executable);
        $this->xmlWriter->writeAttribute('executed', $executed);
        $this->xmlWriter->writeAttribute('coverage', $coverage);

        if ($executableBranches > 0) {
            $this->xmlWriter->writeAttribute('executableBranches', (string) $executableBranches);
            $this->xmlWriter->writeAttribute('executedBranches', (string) $executedBranches);
            $this->xmlWriter->writeAttribute('executablePaths', (string) $executablePaths);
            $this->xmlWriter->writeAttribute('executedPaths', (string) $executedPaths);
        }
    }
}
