<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009-2014 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * The decorator class for module node.
 *
 * @author Martin Hasoň <martin.hason@gmail.com>
 */
abstract class Twig_Node_ModuleDecorator extends Twig_Node_Module
{
    private $module;

    public function __construct(Twig_Node_Module $module)
    {
        $this->module = $module;

        $this->attributes = &$module->attributes;
        $this->lineno = &$module->lineno;
        $this->nodes = &$module->nodes;
        $this->tag = &$module->tag;
    }

    public function setIndex($index)
    {
        return $this->module->setIndex($index);
    }

    public function getLine()
    {
        return $this->module->getLine();
    }

    public function getNodeTag()
    {
        return $this->module->getNodeTag();
    }

    public function hasAttribute($name)
    {
        return $this->module->hasAttribute($name);
    }

    public function getAttribute($name)
    {
        return $this->module->getAttribute($name);
    }

    public function setAttribute($name, $value)
    {
        return $this->module->setAttribute($name, $value);
    }

    public function removeAttribute($name)
    {
        return $this->module->removeAttribute($name);
    }

    public function hasNode($name)
    {
        return $this->module->hasNode($name);
    }

    public function getNode($name)
    {
        return $this->module->getNode($name);
    }

    public function setNode($name, $node = null)
    {
        return $this->module->setNode($name, $node);
    }

    public function removeNode($name)
    {
        return $this->module->removeNode($name);
    }

    public function count()
    {
        return $this->module->count();
    }

    public function getIterator()
    {
        return $this->module->getIterator();
    }

    /**
     * Provides access to the methods from decorated node.
     *
     * @param string $method    The method name
     * @param array  $arguments The parameters to be passed to the method
     *
     * @return mixed
     *
     * @throws BadMethodCallException
     */
    public function __call($method, array $args)
    {
        if (!method_exists($this->module, $method) && !method_exists($this->module, '__call')) {
            throw new \BadMethodCallException(sprintf('Method "%s::%s()" does not exist.', get_class($this->module), $method));
        }

        return call_user_func_array(array($this->module, $method), $args);
    }

    protected function compileGetParent(Twig_Compiler $compiler)
    {
        return $this->module->compileGetParent($compiler);
    }

    protected function compileDisplayBody(Twig_Compiler $compiler)
    {
        return $this->module->compileDisplayBody($compiler);
    }

    protected function compileClassHeader(Twig_Compiler $compiler)
    {
        return $this->module->compileClassHeader($compiler);
    }

    protected function compileConstructor(Twig_Compiler $compiler)
    {
        return $this->module->compileConstructor($compiler);
    }

    protected function compileDisplayHeader(Twig_Compiler $compiler)
    {
        return $this->module->compileDisplayHeader($compiler);
    }

    protected function compileDisplayFooter(Twig_Compiler $compiler)
    {
        return $this->module->compileDisplayFooter($compiler);
    }

    protected function compileMacros(Twig_Compiler $compiler)
    {
        return $this->module->compileMacros($compiler);
    }

    protected function compileGetTemplateName(Twig_Compiler $compiler)
    {
        return $this->module->compileGetTemplatename($compiler);
    }

    protected function compileIsTraitable(Twig_Compiler $compiler)
    {
        return $this->module->compileIsTraitable($compiler);
    }

    protected function compileDebugInfo(Twig_Compiler $compiler)
    {
        return $this->module->compileDebugInfo($compiler);
    }

    protected function compileLoadTemplate(Twig_Compiler $compiler, $node, $var)
    {
        return $this->module->compileLoadTemplate($compiler, $node, $var);
    }

    protected function compileClassFooter(Twig_Compiler $compiler)
    {
        return $this->module->compileClassFooter($compiler);
    }
}
