<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 * (c) 2009 Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Represents a module node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Twig_Node_SandboxedModule extends Twig_Node_Module
{
    protected $usedFilters;
    protected $usedTags;
    protected $usedFunctions;

    public function __construct(Twig_Node_Module $node, array $usedFilters, array $usedTags, array $usedFunctions)
    {
        parent::__construct($node->getNode('body'), $node->getNode('parent'), $node->getNode('blocks'), $node->getNode('macros'), $node->getNode('traits'), $node->getAttribute('embedded_templates'), $node->getAttribute('filename'));

        $this->setAttribute('index', $node->getAttribute('index'));

        $this->usedFilters = $usedFilters;
        $this->usedTags = $usedTags;
        $this->usedFunctions = $usedFunctions;
    }

    protected function compileDisplayBody(Twig_Compiler $compiler)
    {
        $compiler->write("\$this->checkSecurity();\n");

        parent::compileDisplayBody($compiler);
    }

    protected function compileDisplayFooter(Twig_Compiler $compiler)
    {
        parent::compileDisplayFooter($compiler);

        $tags = $filters = $functions = array();
        foreach (array('tags', 'filters', 'functions') as $type) {
            foreach ($this->{'used'.ucfirst($type)} as $name => $node) {
                if ($node instanceof Twig_Node) {
                    ${$type}[$name] = $node->getLine();
                } else {
                    ${$type}[$node] = null;
                }
            }
        }

        $compiler
            ->write("protected function checkSecurity()\n", "{\n")
            ->indent()
            ->write("\$tags = ")->repr(array_filter($tags))->raw(";\n")
            ->write("\$filters = ")->repr(array_filter($filters))->raw(";\n")
            ->write("\$functions = ")->repr(array_filter($functions))->raw(";\n\n")
            ->write("try {\n")
            ->indent()
            ->write("\$this->env->getExtension('sandbox')->checkSecurity(\n")
            ->indent()
            ->write(!$tags ? "array(),\n" : "array('".implode("', '", array_keys($tags))."'),\n")
            ->write(!$filters ? "array(),\n" : "array('".implode("', '", array_keys($filters))."'),\n")
            ->write(!$functions ? "array()\n" : "array('".implode("', '", array_keys($functions))."')\n")
            ->outdent()
            ->write(");\n")
            ->outdent()
            ->write("} catch (Twig_Sandbox_SecurityError \$e) {\n")
            ->indent()
            ->write("\$e->setTemplateFile(\$this->getTemplateName());\n\n")
            ->write("if (\$e instanceof Twig_Sandbox_SecurityNotAllowedTagError && isset(\$tags[\$e->getTagName()])) {\n")
            ->indent()
            ->write("\$e->setTemplateLine(\$tags[\$e->getTagName()]);\n")
            ->outdent()
            ->write("} elseif (\$e instanceof Twig_Sandbox_SecurityNotAllowedFilterError && isset(\$filters[\$e->getFilterName()])) {\n")
            ->indent()
            ->write("\$e->setTemplateLine(\$filters[\$e->getFilterName()]);\n")
            ->outdent()
            ->write("} elseif (\$e instanceof Twig_Sandbox_SecurityNotAllowedFunctionError && isset(\$functions[\$e->getFunctionName()])) {\n")
            ->indent()
            ->write("\$e->setTemplateLine(\$functions[\$e->getFunctionName()]);\n")
            ->outdent()
            ->write("}\n\n")
            ->write("throw \$e;\n")
            ->outdent()
            ->write("}\n")
            ->outdent()
            ->write("}\n\n")
        ;
    }
}
