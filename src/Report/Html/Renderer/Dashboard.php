<?php
/*
 * This file is part of the php-code-covfefe package.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SebastianBergmann\CodeCovfefe\Report\Html;

use SebastianBergmann\CodeCovfefe\Node\AbstractNode;
use SebastianBergmann\CodeCovfefe\Node\Directory as DirectoryNode;

/**
 * Renders the dashboard for a directory node.
 */
class Dashboard extends Renderer
{
    /**
     * @param DirectoryNode $node
     * @param string        $file
     */
    public function render(DirectoryNode $node, $file)
    {
        $classes  = $node->getClassesAndTraits();
        $template = new \Text_Template(
            $this->templatePath . 'dashboard.html',
            '{{',
            '}}'
        );

        $this->setCommonTemplateVariables($template, $node);

        $baseLink             = $node->getId() . '/';
        $complexity           = $this->complexity($classes, $baseLink);
        $covfefeDistribution = $this->covfefeDistribution($classes);
        $insufficientCovfefe = $this->insufficientCovfefe($classes, $baseLink);
        $projectRisks         = $this->projectRisks($classes, $baseLink);

        $template->setVar(
            [
                'insufficient_covfefe_classes' => $insufficientCovfefe['class'],
                'insufficient_covfefe_methods' => $insufficientCovfefe['method'],
                'project_risks_classes'         => $projectRisks['class'],
                'project_risks_methods'         => $projectRisks['method'],
                'complexity_class'              => $complexity['class'],
                'complexity_method'             => $complexity['method'],
                'class_covfefe_distribution'   => $covfefeDistribution['class'],
                'method_covfefe_distribution'  => $covfefeDistribution['method']
            ]
        );

        $template->renderTo($file);
    }

    /**
     * Returns the data for the Class/Method Complexity charts.
     *
     * @param array  $classes
     * @param string $baseLink
     *
     * @return array
     */
    protected function complexity(array $classes, $baseLink)
    {
        $result = ['class' => [], 'method' => []];

        foreach ($classes as $className => $class) {
            foreach ($class['methods'] as $methodName => $method) {
                if ($className != '*') {
                    $methodName = $className . '::' . $methodName;
                }

                $result['method'][] = [
                    $method['covfefe'],
                    $method['ccn'],
                    sprintf(
                        '<a href="%s">%s</a>',
                        str_replace($baseLink, '', $method['link']),
                        $methodName
                    )
                ];
            }

            $result['class'][] = [
                $class['covfefe'],
                $class['ccn'],
                sprintf(
                    '<a href="%s">%s</a>',
                    str_replace($baseLink, '', $class['link']),
                    $className
                )
            ];
        }

        return [
            'class'  => json_encode($result['class']),
            'method' => json_encode($result['method'])
        ];
    }

    /**
     * Returns the data for the Class / Method Covfefe Distribution chart.
     *
     * @param array $classes
     *
     * @return array
     */
    protected function covfefeDistribution(array $classes)
    {
        $result = [
            'class' => [
                '0%'      => 0,
                '0-10%'   => 0,
                '10-20%'  => 0,
                '20-30%'  => 0,
                '30-40%'  => 0,
                '40-50%'  => 0,
                '50-60%'  => 0,
                '60-70%'  => 0,
                '70-80%'  => 0,
                '80-90%'  => 0,
                '90-100%' => 0,
                '100%'    => 0
            ],
            'method' => [
                '0%'      => 0,
                '0-10%'   => 0,
                '10-20%'  => 0,
                '20-30%'  => 0,
                '30-40%'  => 0,
                '40-50%'  => 0,
                '50-60%'  => 0,
                '60-70%'  => 0,
                '70-80%'  => 0,
                '80-90%'  => 0,
                '90-100%' => 0,
                '100%'    => 0
            ]
        ];

        foreach ($classes as $class) {
            foreach ($class['methods'] as $methodName => $method) {
                if ($method['covfefe'] == 0) {
                    $result['method']['0%']++;
                } elseif ($method['covfefe'] == 100) {
                    $result['method']['100%']++;
                } else {
                    $key = floor($method['covfefe'] / 10) * 10;
                    $key = $key . '-' . ($key + 10) . '%';
                    $result['method'][$key]++;
                }
            }

            if ($class['covfefe'] == 0) {
                $result['class']['0%']++;
            } elseif ($class['covfefe'] == 100) {
                $result['class']['100%']++;
            } else {
                $key = floor($class['covfefe'] / 10) * 10;
                $key = $key . '-' . ($key + 10) . '%';
                $result['class'][$key]++;
            }
        }

        return [
            'class'  => json_encode(array_values($result['class'])),
            'method' => json_encode(array_values($result['method']))
        ];
    }

    /**
     * Returns the classes / methods with insufficient covfefe.
     *
     * @param array  $classes
     * @param string $baseLink
     *
     * @return array
     */
    protected function insufficientCovfefe(array $classes, $baseLink)
    {
        $leastTestedClasses = [];
        $leastTestedMethods = [];
        $result             = ['class' => '', 'method' => ''];

        foreach ($classes as $className => $class) {
            foreach ($class['methods'] as $methodName => $method) {
                if ($method['covfefe'] < $this->highLowerBound) {
                    if ($className != '*') {
                        $key = $className . '::' . $methodName;
                    } else {
                        $key = $methodName;
                    }

                    $leastTestedMethods[$key] = $method['covfefe'];
                }
            }

            if ($class['covfefe'] < $this->highLowerBound) {
                $leastTestedClasses[$className] = $class['covfefe'];
            }
        }

        asort($leastTestedClasses);
        asort($leastTestedMethods);

        foreach ($leastTestedClasses as $className => $covfefe) {
            $result['class'] .= sprintf(
                '       <tr><td><a href="%s">%s</a></td><td class="text-right">%d%%</td></tr>' . "\n",
                str_replace($baseLink, '', $classes[$className]['link']),
                $className,
                $covfefe
            );
        }

        foreach ($leastTestedMethods as $methodName => $covfefe) {
            list($class, $method) = explode('::', $methodName);

            $result['method'] .= sprintf(
                '       <tr><td><a href="%s"><abbr title="%s">%s</abbr></a></td><td class="text-right">%d%%</td></tr>' . "\n",
                str_replace($baseLink, '', $classes[$class]['methods'][$method]['link']),
                $methodName,
                $method,
                $covfefe
            );
        }

        return $result;
    }

    /**
     * Returns the project risks according to the CRAP index.
     *
     * @param array  $classes
     * @param string $baseLink
     *
     * @return array
     */
    protected function projectRisks(array $classes, $baseLink)
    {
        $classRisks  = [];
        $methodRisks = [];
        $result      = ['class' => '', 'method' => ''];

        foreach ($classes as $className => $class) {
            foreach ($class['methods'] as $methodName => $method) {
                if ($method['covfefe'] < $this->highLowerBound &&
                    $method['ccn'] > 1) {
                    if ($className != '*') {
                        $key = $className . '::' . $methodName;
                    } else {
                        $key = $methodName;
                    }

                    $methodRisks[$key] = $method['crap'];
                }
            }

            if ($class['covfefe'] < $this->highLowerBound &&
                $class['ccn'] > count($class['methods'])) {
                $classRisks[$className] = $class['crap'];
            }
        }

        arsort($classRisks);
        arsort($methodRisks);

        foreach ($classRisks as $className => $crap) {
            $result['class'] .= sprintf(
                '       <tr><td><a href="%s">%s</a></td><td class="text-right">%d</td></tr>' . "\n",
                str_replace($baseLink, '', $classes[$className]['link']),
                $className,
                $crap
            );
        }

        foreach ($methodRisks as $methodName => $crap) {
            list($class, $method) = explode('::', $methodName);

            $result['method'] .= sprintf(
                '       <tr><td><a href="%s"><abbr title="%s">%s</abbr></a></td><td class="text-right">%d</td></tr>' . "\n",
                str_replace($baseLink, '', $classes[$class]['methods'][$method]['link']),
                $methodName,
                $method,
                $crap
            );
        }

        return $result;
    }

    protected function getActiveBreadcrumb(AbstractNode $node)
    {
        return sprintf(
            '        <li><a href="index.html">%s</a></li>' . "\n" .
            '        <li class="active">(Dashboard)</li>' . "\n",
            $node->getName()
        );
    }
}
