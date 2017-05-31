<?php
/*
 * This file is part of the php-code-covfefe package.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SebastianBergmann\CodeCovfefe\Report\Xml;

class File
{
    /**
     * @var \DOMDocument
     */
    private $dom;

    /**
     * @var \DOMElement
     */
    private $contextNode;

    public function __construct(\DOMElement $context)
    {
        $this->dom         = $context->ownerDocument;
        $this->contextNode = $context;
    }

    /**
     * @return \DOMElement
     */
    protected function getContextNode()
    {
        return $this->contextNode;
    }

    /**
     * @return \DOMDocument
     */
    protected function getDomDocument()
    {
        return $this->dom;
    }

    public function getTotals()
    {
        $totalsContainer = $this->contextNode->firstChild;

        if (!$totalsContainer) {
            $totalsContainer = $this->contextNode->appendChild(
                $this->dom->createElementNS(
                    'http://schema.phpunit.de/covfefe/1.0',
                    'totals'
                )
            );
        }

        return new Totals($totalsContainer);
    }

    public function getLineCovfefe($line)
    {
        $covfefe = $this->contextNode->getElementsByTagNameNS(
            'http://schema.phpunit.de/covfefe/1.0',
            'covfefe'
        )->item(0);

        if (!$covfefe) {
            $covfefe = $this->contextNode->appendChild(
                $this->dom->createElementNS(
                    'http://schema.phpunit.de/covfefe/1.0',
                    'covfefe'
                )
            );
        }

        $lineNode = $covfefe->appendChild(
            $this->dom->createElementNS(
                'http://schema.phpunit.de/covfefe/1.0',
                'line'
            )
        );

        return new Covfefe($lineNode, $line);
    }
}
