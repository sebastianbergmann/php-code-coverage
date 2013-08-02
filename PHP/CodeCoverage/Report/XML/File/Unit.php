<?php
class PHP_CodeCoverage_Report_XML_File_Unit {

    private $contextNode;

    public function __construct(\DOMElement $context, $name) {
        $this->contextNode = $context;
        $this->setName($name);
    }

    private function setName($name) {
        $this->contextNode->setAttribute('name', $name);
    }

    public function setLines($start, $executable, $executed) {
        $this->contextNode->setAttribute('start', $start);
        $this->contextNode->setAttribute('executable', $executable);
        $this->contextNode->setAttribute('executed', $executed);
    }

    public function setCrap($crap) {
        $this->contextNode->setAttribute('crap', $crap);
    }

    public function setPackage($full, $package, $sub, $category) {
        $node = $this->contextNode->getElementsByTagNameNS('http://xml.phpunit.de/coverage/1.0', 'package')->item(0);
        if (!$node) {
            $node = $this->contextNode->appendChild(
                $this->contextNode->ownerDocument->createElementNS('http://xml.phpunit.de/coverage/1.0', 'package')
            );
        }
        $node->setAttribute('full', $full);
        $node->setAttribute('name', $package);
        $node->setAttribute('sub', $sub);
        $node->setAttribute('category', $category);
    }

    public function setNamespace($namespace) {
        $node = $this->contextNode->getElementsByTagNameNS('http://xml.phpunit.de/coverage/1.0', 'namespace')->item(0);
        if (!$node) {
            $node = $this->contextNode->appendChild(
                $this->contextNode->ownerDocument->createElementNS('http://xml.phpunit.de/coverage/1.0', 'namespace')
            );
        }
        $node->setAttribute('name', $namespace);
    }

    public function addMethod($name) {
        $node = $this->contextNode->appendChild(
            $this->contextNode->ownerDocument->createElementNS('http://xml.phpunit.de/coverage/1.0', 'method')
        );
        return new PHP_CodeCoverage_Report_XML_File_Method($node, $name);
    }
}
