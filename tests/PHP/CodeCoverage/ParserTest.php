<?php
class ParserTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PHP_CodeCoverage_Parser
     */
    private $parser;

    protected function setUp()
    {
        $this->parser = new PHP_CodeCoverage_Parser;
    }

    /**
     * @param        string $filename
     * @param        array  $expectedResult
     * @dataProvider ignoredLinesProvider
     * @covers       PHP_CodeCoverage_Parser::getLinesToBeIgnored
     */
    public function testLinesToBeIgnoredAreParsedCorrectly($filename, array $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->parser->getLinesToBeIgnored($filename));
    }

    /**
     * @return array
     */
    public function ignoredLinesProvider()
    {
        return array(
            array(
                TEST_FILES_PATH . 'source_with_ignore.php',
                array(
                    1,
                    3,
                    4,
                    5,
                    7,
                    8,
                    9,
                    10,
                    11,
                    12,
                    13,
                    14,
                    15,
                    16,
                    17,
                    18,
                    19,
                    20,
                    21,
                    22,
                    23,
                    24,
                    25,
                    26,
                    27,
                    28,
                    30,
                    32,
                    33,
                    34,
                    35,
                    36,
                    37,
                    38
                )
            ),
            array(
                TEST_FILES_PATH . 'source_without_ignore.php',
                array(1, 5)
            ),
            array(
                TEST_FILES_PATH . 'source_with_class_and_anonymous_function.php',
                array(
                    1,
                    2,
                    3,
                    4,
                    5,
                    8,
                    11,
                    15,
                    16,
                    19,
                    20
                )
            ),
            array(
                TEST_FILES_PATH . 'source_with_oneline_annotations.php',
                array(
                    1,
                    2,
                    3,
                    4,
                    5,
                    6,
                    7,
                    8,
                    9,
                    10,
                    11,
                    12,
                    13,
                    14,
                    15,
                    16,
                    18,
                    20,
                    21,
                    23,
                    24,
                    25,
                    27,
                    28,
                    29,
                    30,
                    31,
                    32,
                    33,
                    34,
                    37
                )
            )
        );
    }
}
