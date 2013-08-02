<?php
class PHP_CodeCoverage_Report_XML_File_Method {

    private $contextNode;

    public function __construct(\DOMElement $context, $name)
    {
        $this->contextNode = $context;
        $this->setName($name);
    }

    private function setName($name)
    {
        $this->contextNode->setAttribute('name', $name);
    }

    public function setSignature($signature)
    {
        $this->contextNode->setAttribute('signature', $signature);
    }

    public function setLines($start, $end = NULL)
    {
        $this->contextNode->setAttribute('start', $start);
        if ($end !== NULL) {
            $this->contextNode->setAttribute('end', $end);
        }
    }

    public function setTotals($executable, $executed, $coverage)
    {
        $this->contextNode->setAttribute('executable', $executable);
        $this->contextNode->setAttribute('executed', $executed);
        $this->contextNode->setAttribute('coverage', $coverage);
    }

    public function setCrap($crap)
    {
        $this->contextNode->setAttribute('crap', $crap);
    }

}