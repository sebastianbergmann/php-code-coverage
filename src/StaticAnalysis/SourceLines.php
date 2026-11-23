<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\StaticAnalysis;

use function file_get_contents;
use function is_file;
use function is_readable;
use function sort;
use function sprintf;
use SebastianBergmann\CodeCoverage\Serialization\FileCouldNotBeReadException;

/**
 * What the syntax of a source file tells about its lines: which lines belong
 * to the same statement, and which method or function a line belongs to.
 *
 * Code coverage data only has the lines that are executable. Which lines of
 * a statement that spans multiple lines are executable depends on how the
 * executable lines were determined when the data was collected. This allows
 * to relate a line of such a statement that is not executable, the line of
 * an argument of a function call for instance, to the lines of that statement
 * that are.
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final readonly class SourceLines
{
    /**
     * @var array<positive-int, non-empty-list<positive-int>>
     */
    private array $linesOfStatements;

    /**
     * @var list<array{name: non-empty-string, startLine: positive-int, endLine: positive-int}>
     */
    private array $codeUnits;

    /**
     * A file that cannot be parsed has no lines that are known to belong to
     * a statement, a method, or a function.
     *
     * @param non-empty-string $file
     *
     * @throws FileCouldNotBeReadException
     */
    public static function of(string $file): self
    {
        if (!is_file($file) || !is_readable($file)) {
            throw new FileCouldNotBeReadException(
                sprintf(
                    'File "%s" could not be read',
                    $file,
                ),
            );
        }

        $sourceCode = file_get_contents($file);

        if ($sourceCode === false) {
            // @codeCoverageIgnoreStart
            throw new FileCouldNotBeReadException(
                sprintf(
                    'File "%s" could not be read',
                    $file,
                ),
            );
            // @codeCoverageIgnoreEnd
        }

        $analysisResult = (new ParsingSourceAnalyser)->analyse($file, $sourceCode, false, false);

        $codeUnits = [];

        foreach ($analysisResult->classes() as $class) {
            foreach ($class->methods() as $method) {
                $codeUnits[] = [
                    'name'      => $class->namespacedName() . '::' . $method->name(),
                    'startLine' => $method->startLine(),
                    'endLine'   => $method->endLine(),
                ];
            }
        }

        foreach ($analysisResult->traits() as $trait) {
            foreach ($trait->methods() as $method) {
                $codeUnits[] = [
                    'name'      => $trait->namespacedName() . '::' . $method->name(),
                    'startLine' => $method->startLine(),
                    'endLine'   => $method->endLine(),
                ];
            }
        }

        foreach ($analysisResult->functions() as $function) {
            $codeUnits[] = [
                'name'      => $function->namespacedName(),
                'startLine' => $function->startLine(),
                'endLine'   => $function->endLine(),
            ];
        }

        return new self($analysisResult->executableLines(), $codeUnits);
    }

    /**
     * @param array<positive-int, int>                                                            $statements
     * @param list<array{name: non-empty-string, startLine: positive-int, endLine: positive-int}> $codeUnits
     */
    private function __construct(array $statements, array $codeUnits)
    {
        $linesByStatement = [];

        foreach ($statements as $line => $statement) {
            $linesByStatement[$statement][] = $line;
        }

        $linesOfStatements = [];

        foreach ($linesByStatement as $lines) {
            sort($lines);

            foreach ($lines as $line) {
                $linesOfStatements[$line] = $lines;
            }
        }

        $this->linesOfStatements = $linesOfStatements;
        $this->codeUnits         = $codeUnits;
    }

    /**
     * Returns the lines of the statement that a line belongs to, including
     * that line. The lines of a statement are the lines that are counted as
     * executable when the executable lines of a file are determined from its
     * syntax.
     *
     * Returns an empty list for a line that does not belong to a statement,
     * such as an empty line, the line of a comment, or the line of the
     * declaration of a method.
     *
     * An operand of the &&, ||, and ?-> operators that is not always
     * evaluated, and that is on a line of its own, is not a line of the
     * statement the operator is used in: whether it was executed does not
     * depend on whether the other lines of that statement were executed.
     *
     * @return list<positive-int>
     */
    public function linesOfStatementAt(int $line): array
    {
        return $this->linesOfStatements[$line] ?? [];
    }

    /**
     * Returns the name of the method ("Example\Greeter::greet", for example)
     * or function ("Example\greet", for example) that a line belongs to.
     *
     * A line of a method of an anonymous class belongs to the method or
     * function that the anonymous class is declared in.
     *
     * Returns null for a line that does not belong to a method or function.
     *
     * @return ?non-empty-string
     */
    public function codeUnitAt(int $line): ?string
    {
        foreach ($this->codeUnits as $codeUnit) {
            if ($line >= $codeUnit['startLine'] && $line <= $codeUnit['endLine']) {
                return $codeUnit['name'];
            }
        }

        return null;
    }
}
