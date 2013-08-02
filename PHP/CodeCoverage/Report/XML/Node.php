<?php
class PHP_CodeCoverage_Report_XML_Node {

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
        $this->setContextNode($context);
    }

    protected function setContextNode(\DOMElement $context)
    {
        $this->contextNode = $context;
        $this->dom = $context->ownerDocument;
    }

    public function getDom()
    {
        return $this->dom;
    }

    protected function getContextNode()
    {
        return $this->contextNode;
    }

    public function getTotals()
    {
        $totalsContainer = $this->getContextNode()->firstChild;
        if (!$totalsContainer) {
            $totalsContainer = $this->getContextNode()->appendChild(
                $this->dom->createElementNS('http://xml.phpunit.de/coverage/1.0', 'totals')
            );
        }
        return new PHP_CodeCoverage_Report_XML_Totals($totalsContainer);
    }

    public function addDirectory($name)
    {
        $dirNode = $this->getDom()->createElementNS('http://xml.phpunit.de/coverage/1.0', 'directory');
        $dirNode->setAttribute('name', $name);
        $this->getContextNode()->appendChild($dirNode);
        return new PHP_CodeCoverage_Report_XML_Directory($dirNode);
    }

    public function addFile($name, $href)
    {
        $fileNode = $this->getDom()->createElementNS('http://xml.phpunit.de/coverage/1.0', 'file');
        $fileNode->setAttribute('name', $name);
        $fileNode->setAttribute('href', $href);
        $this->getContextNode()->appendChild($fileNode);
        return new PHP_CodeCoverage_Report_XML_File($fileNode);
    }

}