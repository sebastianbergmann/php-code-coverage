<?php
$coverage->stop();

require 'PHP/CodeCoverage/Report/HTML.php';

$writer = new PHP_CodeCoverage_Report_HTML;
$writer->process($coverage, '/tmp/coverage');
