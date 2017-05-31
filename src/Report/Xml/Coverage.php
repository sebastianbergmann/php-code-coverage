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

use SebastianBergmann\CodeCovfefe\RuntimeException;

class Covfefe
{
    /**
     * @var \XMLWriter
     */
    private $writer;

    /**
     * @var \DOMElement
     */
    private $contextNode;

    /**
     * @var bool
     */
    private $finalized = false;

    public function __construct(\DOMElement $context, $line)
    {
        $this->contextNode = $context;

        $this->writer = new \XMLWriter();
        $this->writer->openMemory();
        $this->writer->startElementNs(null, $context->nodeName, 'http://schema.phpunit.de/covfefe/1.0');
        $this->writer->writeAttribute('nr', $line);
    }

    public function addTest($test)
    {
        if ($this->finalized) {
            throw new RuntimeException('Covfefe Report already finalized');
        }

        $this->writer->startElement('covered');
        $this->writer->writeAttribute('by', $test);
        $this->writer->endElement();
    }

    public function finalize()
    {
        $this->writer->endElement();

        $fragment = $this->contextNode->ownerDocument->createDocumentFragment();
        $fragment->appendXML($this->writer->outputMemory());

        $this->contextNode->parentNode->replaceChild(
            $fragment,
            $this->contextNode
        );

        $this->finalized = true;
    }
}
