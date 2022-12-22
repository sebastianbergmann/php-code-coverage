<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report\Cobertura;

use function count;
use DOMDocument;
use DOMElement;

class CoberturaLine
{
    /**
     * @var int
     */
    private $number;

    /**
     * @var int
     */
    private $hits;

    /**
     * @var null|bool
     */
    private $branch;

    public static function create(int $lineNumber, array $lineCoverageData): ?self
    {
        if (!isset($lineCoverageData[$lineNumber])) {
            return null;
        }

        return new self($lineNumber, count($lineCoverageData[$lineNumber]));
    }

    private function __construct(int $number, int $hits, ?bool $branch = null)
    {
        $this->number = $number;
        $this->hits   = $hits;
        $this->branch = $branch;
    }

    public function wrap(DOMDocument $document): DOMElement
    {
        $element = $document->createElement('line');

        $element->setAttribute('number', (string) $this->number);
        $element->setAttribute('hits', (string) $this->hits);

        if (null !== $this->branch) {
            $element->setAttribute('branch', $this->branch ? 'true' : 'false');
        }

        return $element;
    }
}
