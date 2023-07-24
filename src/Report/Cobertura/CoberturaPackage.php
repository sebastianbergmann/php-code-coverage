<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report\Cobertura;

use function array_reduce;
use function explode;
use DOMDocument;
use DOMElement;

class CoberturaPackage
{
    /** @var CoberturaClass[] */
    private $classes = [];

    /**
     * @var string
     */
    private $name;

    public static function packageName(string $className): string
    {
        return explode('\\', $className)[0];
    }

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function addClass(CoberturaClass $class): void
    {
        $this->classes[] = $class;
    }

    public function wrap(DOMDocument $document): DOMElement
    {
        $packageElement = $document->createElement('package');

        $packageElement->setAttribute('name', $this->name);
        $packageElement->setAttribute('line-rate', (string) $this->lineRate());
        $packageElement->setAttribute('branch-rate', (string) $this->branchRate());
        $packageElement->setAttribute('complexity', (string) $this->complexity());

        $classesElement = $document->createElement('classes');

        foreach ($this->classes as $class) {
            $classesElement->appendChild($class->wrap($document));
        }

        $packageElement->appendChild($classesElement);

        return $packageElement;
    }

    public function complexity(): float
    {
        return array_reduce(
            $this->classes,
            static function (float $complexity, CoberturaClass $class)
            {
                return $complexity + $class->getComplexity();
            },
            0
        );
    }

    private function lineRate(): float
    {
        $linesData = array_reduce($this->classes, static function (array $data, CoberturaClass $class)
        {
            $data['valid'] += $class->getLinesValid();
            $data['covered'] += $class->getLinesCovered();

            return $data;
        }, ['valid' => 0, 'covered' => 0]);

        return $linesData['valid'] === 0 ? 0 : $linesData['covered'] / $linesData['valid'];
    }

    private function branchRate(): float
    {
        $branchesData = array_reduce($this->classes, static function (array $data, CoberturaClass $class)
        {
            $data['valid'] += $class->getBranchesValid();
            $data['covered'] += $class->getBranchesCovered();

            return $data;
        }, ['valid' => 0, 'covered' => 0]);

        return $branchesData['valid'] === 0 ? 0 : $branchesData['covered'] / $branchesData['valid'];
    }
}
