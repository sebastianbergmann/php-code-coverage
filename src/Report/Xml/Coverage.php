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

use DOMElement;
use XMLWriter;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class Coverage
{
    private readonly DOMElement $contextNode;
    private readonly string $line;

    public function __construct(DOMElement $context, string $line)
    {
        $this->contextNode = $context;
        $this->line        = $line;
    }

    public function finalize(array $tests): void
    {
        $writer = new XMLWriter;
        $writer->openMemory();
        $writer->startElementNs(null, $this->contextNode->nodeName, Facade::XML_NAMESPACE);
        $writer->writeAttribute('nr', $this->line);

        foreach ($tests as $test) {
            $writer->startElement('covered');
            $writer->writeAttribute('by', $test);
            $writer->endElement();
        }
        $writer->endElement();

        $fragment = $this->contextNode->ownerDocument->createDocumentFragment();
        $fragment->appendXML($writer->outputMemory());

        $this->contextNode->parentNode->replaceChild(
            $fragment,
            $this->contextNode,
        );
    }
}
