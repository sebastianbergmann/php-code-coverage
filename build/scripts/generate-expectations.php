#!/usr/bin/env php
<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Regenerates the expected HTML report files in tests/_files/Report/HTML.
 *
 * The generated files are compared using assertStringMatchesFormatFile(),
 * therefore all values that vary between runs (version, runtime, date, paths)
 * are replaced with %s placeholders. In large file views, everything after
 * the coverage summary table (source listing, footer, scripts) is collapsed
 * to %a: PCRE cannot compile the format description of a fully literal large
 * file view into a regular expression ("regular expression is too large").
 * The collapse is only applied when the fully literal format description
 * cannot be compiled and matched.
 */
require __DIR__ . '/../../tests/bootstrap.php';

use SebastianBergmann\CodeCoverage\CoverageFixtureProvider;
use SebastianBergmann\CodeCoverage\Report\Html\Facade;
use SebastianBergmann\CodeCoverage\Version;
use SebastianBergmann\Environment\Runtime;

function removeDirectory(string $path): void
{
    if (!is_dir($path)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($iterator as $fileInfo) {
        if ($fileInfo->isDir()) {
            rmdir($fileInfo->getPathname());
        } else {
            unlink($fileInfo->getPathname());
        }
    }

    rmdir($path);
}

function withPlaceholders(string $content): string
{
    $runtime = new Runtime;

    $runtimeString = sprintf(
        '<a href="%s" target="_top">%s %s</a>',
        $runtime->getVendorUrl(),
        $runtime->getName(),
        $runtime->getVersion(),
    );

    $content = str_replace($runtimeString, '<a href="%s" target="_top">%s</a>', $content);
    $content = preg_replace('#( at )[^<]+(\.</small>)#', '${1}%s${2}', $content);
    $content = str_replace('php-code-coverage ' . Version::id() . '</a>', 'php-code-coverage %s</a>', $content);
    $content = str_replace('?v=' . Version::id(), '?v=%s', $content);
    $content = str_replace(TEST_FILES_PATH, '%s', $content);

    return str_replace(rtrim(TEST_FILES_PATH, DIRECTORY_SEPARATOR), '%s', $content);
}

function collapseSourceListing(string $content): string
{
    $marker = "    </table>\n   </div>\n";
    $start  = strpos($content, $marker);
    $body   = strpos($content, ' </body>');

    if ($start === false || $body === false) {
        return $content;
    }

    $start += strlen($marker);

    return substr($content, 0, $start) . '%a' . "\n" . substr($content, $body);
}

/**
 * Replicates how assertStringMatchesFormatFile() turns a format description
 * into a regular expression and checks that the format both compiles and
 * matches the given subject.
 */
function formatMatches(string $format, string $subject): bool
{
    $regex = strtr(
        preg_quote($format, '/'),
        [
            '%%' => '%',
            '%e' => preg_quote(DIRECTORY_SEPARATOR, '/'),
            '%s' => '[^\r\n]+',
            '%S' => '[^\r\n]*',
            '%a' => '.+',
            '%A' => '.*',
            '%w' => '\s*',
            '%i' => '[+-]?\d+',
            '%d' => '\d+',
            '%x' => '[0-9a-fA-F]+',
            '%f' => '[+-]?\.?\d+\.?\d*(?:[eE][+-]?\d+)?',
            '%c' => '.',
        ],
    );

    return @preg_match('/^' . $regex . '$/s', $subject) === 1;
}

$provider = new CoverageFixtureProvider;

$scenarios = [
    'CoverageForBankAccount'                => $provider->lineCoverageForBankAccount(...),
    'PathCoverageForBankAccount'            => $provider->pathCoverageForBankAccount(...),
    'PathCoverageForSourceWithoutNamespace' => $provider->pathCoverageForSourceWithoutNamespace(...),
    'CoverageForFileWithIgnoredLines'       => $provider->coverageForFileWithIgnoredLines(...),
    'CoverageForClassWithAnonymousFunction' => $provider->coverageForClassWithAnonymousFunction(...),
];

$expectationPath = TEST_FILES_PATH . 'Report' . DIRECTORY_SEPARATOR . 'HTML' . DIRECTORY_SEPARATOR;
$temporaryPath   = TEST_FILES_PATH . 'tmp';

foreach ($scenarios as $expectationDirectoryName => $coverage) {
    removeDirectory($temporaryPath);

    (new Facade)->process($coverage()->getReport(), $temporaryPath);

    $expectationDirectory = $expectationPath . $expectationDirectoryName;

    if (is_dir($expectationDirectory)) {
        foreach (glob($expectationDirectory . DIRECTORY_SEPARATOR . '*') as $staleFile) {
            unlink($staleFile);
        }
    } else {
        mkdir($expectationDirectory, 0o777, true);
    }

    foreach (glob($temporaryPath . DIRECTORY_SEPARATOR . '*.html') as $generatedFile) {
        $name      = basename($generatedFile);
        $generated = str_replace(PHP_EOL, "\n", file_get_contents($generatedFile));
        $content   = withPlaceholders($generated);

        if (!formatMatches($content, $generated)) {
            $content = collapseSourceListing($content);
        }

        if (!formatMatches($content, $generated)) {
            print 'Format description for ' . $expectationDirectoryName . '/' . $name . ' does not match the generated file' . PHP_EOL;

            exit(1);
        }

        file_put_contents($expectationDirectory . DIRECTORY_SEPARATOR . $name, $content);
    }

    print $expectationDirectoryName . ': ' . count(glob($expectationDirectory . DIRECTORY_SEPARATOR . '*.html')) . ' files' . PHP_EOL;
}

removeDirectory($temporaryPath);
