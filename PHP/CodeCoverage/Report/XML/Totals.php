<?php
class PHP_CodeCoverage_Report_XML_Totals {

    /**
     * @var \DOMNode
     */
    private $container;

    /**
     * @var \DOMElement
     */
    private $linesNode;

    /**
     * @var \DOMElement
     */
    private $methodsNode;

    /**
     * @var \DOMElement
     */
    private $functionsNode;

    /**
     * @var \DOMElement
     */
    private $classesNode;

    /**
     * @var \DOMElement
     */
    private $traitsNode;

    public function __construct(\DOMElement $container)
    {
        $this->container = $container;
        $dom = $container->ownerDocument;
        $this->linesNode     = $dom->createElementNS('http://xml.phpunit.de/coverage/1.0', 'lines');
        $this->methodsNode   = $dom->createElementNS('http://xml.phpunit.de/coverage/1.0', 'methods');
        $this->functionsNode = $dom->createElementNS('http://xml.phpunit.de/coverage/1.0', 'functions');
        $this->classesNode   = $dom->createElementNS('http://xml.phpunit.de/coverage/1.0', 'classes');
        $this->traitsNode    = $dom->createElementNS('http://xml.phpunit.de/coverage/1.0', 'traits');

        $container->appendChild($this->linesNode);
        $container->appendChild($this->methodsNode);
        $container->appendChild($this->functionsNode);
        $container->appendChild($this->classesNode);
        $container->appendChild($this->traitsNode);
    }

    public function getContainer()
    {
        return $this->container;
    }

    public function setNumLines($loc, $cloc, $ncloc, $executable, $executed)
    {
        $this->linesNode->setAttribute('total', $loc);
        $this->linesNode->setAttribute('comments', $cloc);
        $this->linesNode->setAttribute('code', $ncloc);
        $this->linesNode->setAttribute('executable', $executable);
        $this->linesNode->setAttribute('executed', $executed);
        $this->linesNode->setAttribute('percent', PHP_CodeCoverage_Util::percent($executed,$executable, TRUE));
    }

    public function setNumClasses($count, $tested)
    {
        $this->classesNode->setAttribute('count', $count);
        $this->classesNode->setAttribute('tested', $tested);
        $this->classesNode->setAttribute('percent', PHP_CodeCoverage_Util::percent($tested,$count, TRUE));
    }

    public function setNumTraits($count, $tested)
    {
        $this->traitsNode->setAttribute('count', $count);
        $this->traitsNode->setAttribute('tested', $tested);
        $this->traitsNode->setAttribute('percent', PHP_CodeCoverage_Util::percent($tested,$count, TRUE));
    }

    public function setNumMethods($count, $tested)
    {
        $this->methodsNode->setAttribute('count', $count);
        $this->methodsNode->setAttribute('tested', $tested);
        $this->methodsNode->setAttribute('percent', PHP_CodeCoverage_Util::percent($tested,$count, TRUE));
    }

    public function setNumFunctions($count, $tested)
    {
        $this->functionsNode->setAttribute('count', $count);
        $this->functionsNode->setAttribute('tested', $tested);
        $this->functionsNode->setAttribute('percent', PHP_CodeCoverage_Util::percent($tested,$count, TRUE));
    }

}
