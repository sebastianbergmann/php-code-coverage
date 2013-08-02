<?php
class PHP_CodeCoverage_Report_XML_File_Coverage {

    private $contextNode;

    public function __construct(\DOMElement $context, $line)
    {
        $this->contextNode = $context;
        $this->setLine($line);
    }

    private function setLine($line)
    {
        $this->contextNode->setAttribute('nr', $line);
    }

    public function addTest($test)
    {
        $covered = $this->contextNode->appendChild(
            $this->contextNode->ownerDocument->createElementNS('http://xml.phpunit.de/coverage/1.0', 'covered')
        );
        $covered->setAttribute('by', $test);
    }

}