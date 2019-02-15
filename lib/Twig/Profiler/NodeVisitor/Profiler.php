<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @final
 */
class Twig_Profiler_NodeVisitor_Profiler extends \Twig\NodeVisitor\AbstractNodeVisitor
{
    private $extensionName;

    public function __construct($extensionName)
    {
        $this->extensionName = $extensionName;
    }

    protected function doEnterNode(\Twig\Node\Node $node, \Twig\Environment $env)
    {
        return $node;
    }

    protected function doLeaveNode(\Twig\Node\Node $node, \Twig\Environment $env)
    {
        if ($node instanceof \Twig\Node\ModuleNode) {
            $varName = $this->getVarName();
            $node->setNode('display_start', new \Twig\Node\Node([new \Twig\Profiler\Node\EnterProfileNode($this->extensionName, \Twig\Profiler\Profile::TEMPLATE, $node->getTemplateName(), $varName), $node->getNode('display_start')]));
            $node->setNode('display_end', new \Twig\Node\Node([new \Twig\Profiler\Node\LeaveProfileNode($varName), $node->getNode('display_end')]));
        } elseif ($node instanceof \Twig\Node\BlockNode) {
            $varName = $this->getVarName();
            $node->setNode('body', new \Twig\Node\BodyNode([
                new \Twig\Profiler\Node\EnterProfileNode($this->extensionName, \Twig\Profiler\Profile::BLOCK, $node->getAttribute('name'), $varName),
                $node->getNode('body'),
                new \Twig\Profiler\Node\LeaveProfileNode($varName),
            ]));
        } elseif ($node instanceof \Twig\Node\MacroNode) {
            $varName = $this->getVarName();
            $node->setNode('body', new \Twig\Node\BodyNode([
                new \Twig\Profiler\Node\EnterProfileNode($this->extensionName, \Twig\Profiler\Profile::MACRO, $node->getAttribute('name'), $varName),
                $node->getNode('body'),
                new \Twig\Profiler\Node\LeaveProfileNode($varName),
            ]));
        }

        return $node;
    }

    private function getVarName()
    {
        return sprintf('__internal_%s', hash('sha256', $this->extensionName));
    }

    public function getPriority()
    {
        return 0;
    }
}

class_alias('Twig_Profiler_NodeVisitor_Profiler', 'Twig\Profiler\NodeVisitor\ProfilerNodeVisitor', false);
