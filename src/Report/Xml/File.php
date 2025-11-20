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

use function assert;
use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
class File
{
    protected readonly DOMDocument $dom;
    private readonly DOMElement $contextNode;
    private ?DOMNode $lineCoverage = null;

    public function __construct(DOMElement $context)
    {
        $this->dom         = $context->ownerDocument;
        $this->contextNode = $context;
    }

    public function totals(): Totals
    {
        $totalsContainer = $this->contextNode->appendChild(
            $this->dom->createElementNS(
                Facade::XML_NAMESPACE,
                'totals',
            ),
        );

        assert($totalsContainer instanceof DOMElement);

        return new Totals($totalsContainer);
    }

    public function lineCoverage(string $line): Coverage
    {
        if ($this->lineCoverage === null) {
            $this->lineCoverage = $this->contextNode->appendChild(
                $this->dom->createElementNS(
                    Facade::XML_NAMESPACE,
                    'coverage',
                ),
            );
        }
        assert($this->lineCoverage instanceof DOMElement);

        $lineNode = $this->lineCoverage->appendChild(
            $this->dom->createElementNS(
                Facade::XML_NAMESPACE,
                'line',
            ),
        );

        assert($lineNode instanceof DOMElement);

        return new Coverage($lineNode, $line);
    }

    protected function contextNode(): DOMElement
    {
        return $this->contextNode;
    }
}
