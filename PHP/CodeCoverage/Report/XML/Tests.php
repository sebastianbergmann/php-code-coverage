<?php
class PHP_CodeCoverage_Report_XML_Tests {

    private $contextNode;

    public function __construct(\DOMElement $context)
    {
        $this->contextNode = $context;
    }

    public function addTest($test, $result)
    {
        $node = $this->contextNode->appendChild(
            $this->contextNode->ownerDocument->createElementNS('http://xml.phpunit.de/coverage/1.0', 'test')
        );
        $node->setAttribute('name', $test);
        $node->setAttribute('result', $result);
    }

}