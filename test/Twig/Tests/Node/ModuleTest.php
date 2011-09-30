<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once dirname(__FILE__).'/TestCase.php';

class Twig_Tests_Node_ModuleTest extends Twig_Tests_Node_TestCase
{
    /**
     * @covers Twig_Node_Module::__construct
     */
    public function testConstructor()
    {
        $body = new Twig_Node_Text('foo', 0);
        $parent = new Twig_Node_Expression_Constant('layout.twig', 0);
        $blocks = new Twig_Node();
        $macros = new Twig_Node();
        $traits = new Twig_Node();
        $filename = 'foo.twig';
        $node = new Twig_Node_Module($body, $parent, $blocks, $macros, $traits, $filename);

        $this->assertEquals($body, $node->getNode('body'));
        $this->assertEquals($blocks, $node->getNode('blocks'));
        $this->assertEquals($macros, $node->getNode('macros'));
        $this->assertEquals($parent, $node->getNode('parent'));
        $this->assertEquals($filename, $node->getAttribute('filename'));
    }

    /**
     * @covers Twig_Node_Module::compile
     * @covers Twig_Node_Module::compileTemplate
     * @covers Twig_Node_Module::compileMacros
     * @covers Twig_Node_Module::compileClassHeader
     * @covers Twig_Node_Module::compileDisplayHeader
     * @covers Twig_Node_Module::compileDisplayBody
     * @covers Twig_Node_Module::compileDisplayFooter
     * @covers Twig_Node_Module::compileClassFooter
     * @dataProvider getTests
     */
    public function testCompile($node, $source, $environment = null)
    {
        parent::testCompile($node, $source, $environment);
    }

    public function getTests()
    {
        $twig = new Twig_Environment(new Twig_Loader_String());

        $tests = array();

        $body = new Twig_Node_Text('foo', 0);
        $extends = null;
        $blocks = new Twig_Node();
        $macros = new Twig_Node();
        $traits = new Twig_Node();
        $filename = 'foo.twig';

        $node = new Twig_Node_Module($body, $extends, $blocks, $macros, $traits, $filename);
        $tests[] = array($node, <<<EOF
<?php

/* foo.twig */
class __TwigTemplate_be925a7b06dda0dfdbd18a1509f7eb34 extends Twig_Template
{
    protected function doGetParent(array \$context)
    {
        return false;
    }

    protected function doDisplay(array \$context, array \$blocks = array())
    {
        \$context = array_merge(\$this->env->getGlobals(), \$context);

        echo "foo";
    }

    public function getTemplateName()
    {
        return "foo.twig";
    }

    public function isTraitable()
    {
        return true;
    }
}
EOF
        , $twig);

        $import = new Twig_Node_Import(new Twig_Node_Expression_Constant('foo.twig', 0), new Twig_Node_Expression_AssignName('macro', 0), 0);

        $body = new Twig_Node(array($import));
        $extends = new Twig_Node_Expression_Constant('layout.twig', 0);

        $node = new Twig_Node_Module($body, $extends, $blocks, $macros, $traits, $filename);
        $tests[] = array($node, <<<EOF
<?php

/* foo.twig */
class __TwigTemplate_be925a7b06dda0dfdbd18a1509f7eb34 extends Twig_Template
{
    protected function doGetParent(array \$context)
    {
        return "layout.twig";
    }

    protected function doDisplay(array \$context, array \$blocks = array())
    {
        \$context = array_merge(\$this->env->getGlobals(), \$context);

        \$context["macro"] = \$this->env->loadTemplate("foo.twig");
        \$this->getParent(\$context)->display(\$context, array_merge(\$this->blocks, \$blocks));
    }

    public function getTemplateName()
    {
        return "foo.twig";
    }

    public function isTraitable()
    {
        return false;
    }
}
EOF
        , $twig);

        $body = new Twig_Node();
        $extends = new Twig_Node_Expression_Conditional(
                        new Twig_Node_Expression_Constant(true, 0),
                        new Twig_Node_Expression_Constant('foo', 0),
                        new Twig_Node_Expression_Constant('foo', 0),
                        0
                    );

        $node = new Twig_Node_Module($body, $extends, $blocks, $macros, $traits, $filename);
        $tests[] = array($node, <<<EOF
<?php

/* foo.twig */
class __TwigTemplate_be925a7b06dda0dfdbd18a1509f7eb34 extends Twig_Template
{
    protected function doGetParent(array \$context)
    {
        return \$this->env->resolveTemplate(((true) ? ("foo") : ("foo")));
    }

    protected function doDisplay(array \$context, array \$blocks = array())
    {
        \$context = array_merge(\$this->env->getGlobals(), \$context);

        \$this->getParent(\$context)->display(\$context, array_merge(\$this->blocks, \$blocks));
    }

    public function getTemplateName()
    {
        return "foo.twig";
    }

    public function isTraitable()
    {
        return false;
    }
}
EOF
        , $twig);

        return $tests;
    }
}
