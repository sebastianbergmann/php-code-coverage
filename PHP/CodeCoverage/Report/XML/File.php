<?php
class PHP_CodeCoverage_Report_XML_File {

    /**
     * @var \DOMDocument
     */
    protected $dom;

    /**
     * @var \DOMElement
     */
    protected $contextNode;

    public function __construct(\DOMElement $context)
    {
        $this->contextNode = $context;
        $this->dom = $context->ownerDocument;
    }

    public function getTotals()
    {
        $totalsContainer = $this->contextNode->firstChild;
        if (!$totalsContainer) {
            $totalsContainer = $this->contextNode->appendChild(
                $this->dom->createElementNS('http://xml.phpunit.de/coverage/1.0', 'totals')
            );
        }
        return new PHP_CodeCoverage_Report_XML_Totals($totalsContainer);
    }

    public function getLineCoverage($line)
    {
        $coverage = $this->contextNode->getElementsByTagNameNS('http://xml.phpunit.de/coverage/1.0', 'coverage')->item(0);
        if (!$coverage) {
            $coverage = $this->contextNode->appendChild(
                $this->dom->createElementNS('http://xml.phpunit.de/coverage/1.0', 'coverage')
            );
        }
        $lineNode = $coverage->appendChild(
            $this->dom->createElementNS('http://xml.phpunit.de/coverage/1.0', 'line')
        );
        return new PHP_CodeCoverage_Report_XML_File_Coverage($lineNode, $line);
    }

}