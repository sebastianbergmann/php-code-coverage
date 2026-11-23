<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report;

use const DIRECTORY_SEPARATOR;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function json_decode;
use function ltrim;
use function mkdir;
use function realpath;
use function rmdir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Data\RawCodeCoverageData;
use SebastianBergmann\CodeCoverage\Driver\Driver;
use SebastianBergmann\CodeCoverage\FakeDriver;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\Node\AbstractNode;
use SebastianBergmann\CodeCoverage\Node\Builder;
use SebastianBergmann\CodeCoverage\Node\File;
use SebastianBergmann\CodeCoverage\Report\Jsonl\Facade as JsonlFacade;
use SebastianBergmann\CodeCoverage\Serialization\Serializer;
use SebastianBergmann\CodeCoverage\Serialization\Unserializer;
use SebastianBergmann\CodeCoverage\TestCase;
use SebastianBergmann\CodeCoverage\Util\PathReducer;

#[CoversClass(AbstractNode::class)]
#[CoversClass(Builder::class)]
#[CoversClass(Cobertura::class)]
#[CoversClass(JsonlFacade::class)]
#[CoversClass(PathReducer::class)]
#[CoversClass(Serializer::class)]
#[Medium]
final class FilesThatOnlyShareTheRootDirectoryTest extends TestCase
{
    /**
     * @var non-empty-string
     */
    private string $fileInThisProject;

    /**
     * @var non-empty-string
     */
    private string $temporaryDirectory;

    /**
     * @var non-empty-string
     */
    private string $fileInTemporaryDirectory;

    protected function setUp(): void
    {
        $fileInThisProject  = realpath(TEST_FILES_PATH . 'BankAccount.php');
        $temporaryDirectory = realpath(sys_get_temp_dir());

        $this->assertNotFalse($fileInThisProject);
        $this->assertNotSame('', $fileInThisProject);
        $this->assertNotFalse($temporaryDirectory);

        if (DIRECTORY_SEPARATOR !== '/' ||
            (explode('/', $fileInThisProject)[1] ?? '') === (explode('/', $temporaryDirectory)[1] ?? '')) {
            $this->markTestSkipped('Requires that the root directory is all that the directory for temporary files and this project have in common');
        }

        $this->fileInThisProject        = $fileInThisProject;
        $this->temporaryDirectory       = $temporaryDirectory . '/' . uniqid('php-code-coverage-');
        $this->fileInTemporaryDirectory = $this->temporaryDirectory . '/source.php';

        mkdir($this->temporaryDirectory);

        file_put_contents(
            $this->fileInTemporaryDirectory,
            <<<'EOT'
<?php declare(strict_types=1);
function functionInTemporaryDirectory(): bool
{
    return true;
}

EOT,
        );
    }

    protected function tearDown(): void
    {
        if (isset($this->temporaryDirectory) && is_dir($this->temporaryDirectory)) {
            unlink($this->fileInTemporaryDirectory);
            rmdir($this->temporaryDirectory);
        }

        $this->removeTemporaryFiles();
    }

    public function testReportIsForTheRootDirectory(): void
    {
        $report = $this->codeCoverage()->getReport();

        $this->assertSame('/', $report->name());
        $this->assertSame('/', $report->pathAsString());

        $files = [];

        foreach ($report as $node) {
            if ($node instanceof File) {
                $files[] = $node->pathAsString();
            }
        }

        $this->assertEqualsCanonicalizing([$this->fileInThisProject, $this->fileInTemporaryDirectory], $files);
    }

    public function testNoDataIsLostWhenReportIsGeneratedFromObject(): void
    {
        $summary = Facade::fromObject($this->codeCoverage())->summary();

        $this->assertSame(7, $summary->numberOfExecutableLines());
        $this->assertSame(2, $summary->numberOfExecutedLines());
    }

    public function testNoDataIsLostWhenReportIsGeneratedFromSerializedData(): void
    {
        $target = TEST_FILES_PATH . 'tmp/serialized.php';

        (new Serializer)->serialize($target, $this->codeCoverage());

        $data = (new Unserializer)->unserialize($target);

        $this->assertSame('/', $data['basePath']);

        $this->assertEqualsCanonicalizing(
            [ltrim($this->fileInThisProject, '/'), ltrim($this->fileInTemporaryDirectory, '/')],
            $data['codeCoverage']->coveredFiles(),
        );

        $summary = Facade::fromSerializedData($data)->summary();

        $this->assertSame(7, $summary->numberOfExecutableLines());
        $this->assertSame(2, $summary->numberOfExecutedLines());
    }

    public function testCoberturaReportHasPathsThatAreRelativeToTheRootDirectory(): void
    {
        $xml = (new Cobertura)->process($this->codeCoverage()->getReport());

        $this->assertStringContainsString('<source>/</source>', $xml);
        $this->assertStringContainsString('filename="' . ltrim($this->fileInThisProject, '/') . '"', $xml);
        $this->assertStringContainsString('filename="' . ltrim($this->fileInTemporaryDirectory, '/') . '"', $xml);
    }

    public function testJsonlReportHasPathsThatAreRelativeToTheRootDirectory(): void
    {
        $target = TEST_FILES_PATH . 'tmp/jsonl';

        (new JsonlFacade)->process($this->codeCoverage()->getReport(), $target);

        $meta = json_decode((string) file_get_contents($target . '/meta.json'), true);

        $this->assertIsArray($meta);
        $this->assertArrayHasKey('sourceRoot', $meta);
        $this->assertSame('/', $meta['sourceRoot']);

        $coverage = (string) file_get_contents($target . '/coverage.jsonl');

        $this->assertStringContainsString('"file":"' . ltrim($this->fileInThisProject, '/') . '"', $coverage);
        $this->assertStringContainsString('"file":"' . ltrim($this->fileInTemporaryDirectory, '/') . '"', $coverage);
    }

    private function codeCoverage(): CodeCoverage
    {
        $driver = new FakeDriver(
            RawCodeCoverageData::fromLineCoverage(
                [
                    $this->fileInThisProject        => [8 => Driver::LINE_EXECUTED],
                    $this->fileInTemporaryDirectory => [4 => Driver::LINE_EXECUTED],
                ],
            ),
        );

        $filter = new Filter;

        $filter->includeFiles([$this->fileInThisProject, $this->fileInTemporaryDirectory]);

        $codeCoverage = new CodeCoverage($driver, $filter);

        $codeCoverage->start('test');
        $codeCoverage->stop();

        return $codeCoverage;
    }
}
