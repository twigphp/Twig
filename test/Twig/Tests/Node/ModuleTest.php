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
        $extends = 'layout.twig';
        $blocks = new Twig_Node();
        $macros = new Twig_Node();
        $filename = 'foo.twig';
        $node = new Twig_Node_Module($body, $extends, $blocks, $macros, $filename);

        $this->assertEquals($body, $node->body);
        $this->assertEquals($blocks, $node->blocks);
        $this->assertEquals($macros, $node->macros);
        $this->assertEquals($filename, $node['filename']);
        $this->assertEquals($extends, $node['extends']);
    }

    /**
     * @covers Twig_Node_Module::compile
     * @covers Twig_Node_Module::compileTemplate
     * @covers Twig_Node_Module::compileMacros
     * @covers Twig_Node_Module::compileClassHeader
     * @covers Twig_Node_Module::compileDisplayHeader
     * @covers Twig_Node_Module::compileDisplayBody
     * @covers Twig_Node_Module::compileDisplayFooter
     * @covers Twig_Node_Module::compileGetName
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
        $filename = 'foo.twig';

        $node = new Twig_Node_Module($body, $extends, $blocks, $macros, $filename);
        $tests[] = array($node, <<<EOF
<?php

/* foo.twig */
class __TwigTemplate_be925a7b06dda0dfdbd18a1509f7eb34 extends Twig_Template
{
    public function display(array \$context)
    {
        echo "foo";
    }

    public function getName()
    {
        return "foo.twig";
    }

}

class __TwigTemplate_be925a7b06dda0dfdbd18a1509f7eb34_Macro extends Twig_Macro
{
}
EOF
        , $twig);

        $import = new Twig_Node_Import(new Twig_Node_Expression_Constant('foo.twig', 0), new Twig_Node_Expression_AssignName('macro', 0), 0);

        $body = new Twig_Node(array($import, new Twig_Node_Text('foo', 0)));
        $extends = 'layout.twig';
        $blocks = new Twig_Node();
        $macros = new Twig_Node();
        $filename = 'foo.twig';

        $node = new Twig_Node_Module($body, $extends, $blocks, $macros, $filename);
        $tests[] = array($node, <<<EOF
<?php

\$this->loadTemplate("layout.twig");

/* foo.twig */
class __TwigTemplate_be925a7b06dda0dfdbd18a1509f7eb34 extends __TwigTemplate_d8fb9d03f55738ff78518e1bc2741faf
{
    public function display(array \$context)
    {
        \$context['macro'] = \$this->env->loadTemplate("foo.twig", true);

        parent::display(\$context);
    }

    public function getName()
    {
        return "foo.twig";
    }

}

class __TwigTemplate_be925a7b06dda0dfdbd18a1509f7eb34_Macro extends Twig_Macro
{
}
EOF
        , $twig);

        return $tests;
    }
}
