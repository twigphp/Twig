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

class Twig_Tests_Node_SandboxedModuleTest extends Twig_Tests_Node_TestCase
{
    /**
     * @covers Twig_Node_SandboxedModule::__construct
     */
    public function testConstructor()
    {
        $body = new Twig_Node_Text('foo', 0);
        $parent = new Twig_Node_Expression_Constant('layout.twig', 0);
        $blocks = new Twig_Node();
        $macros = new Twig_Node();
        $filename = 'foo.twig';
        $node = new Twig_Node_Module($body, $parent, $blocks, $macros, $filename);
        $node = new Twig_Node_SandboxedModule($node, array('for'), array('upper'));

        $this->assertEquals($body, $node->body);
        $this->assertEquals($blocks, $node->blocks);
        $this->assertEquals($macros, $node->macros);
        $this->assertEquals($parent, $node->parent);
        $this->assertEquals($filename, $node['filename']);
    }

    /**
     * @covers Twig_Node_SandboxedModule::compile
     * @covers Twig_Node_SandboxedModule::compileDisplayBody
     * @covers Twig_Node_SandboxedModule::compileDisplayFooter
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
        $node = new Twig_Node_SandboxedModule($node, array('for'), array('upper'));

        $tests[] = array($node, <<<EOF
<?php

/* foo.twig */
class __TwigTemplate_be925a7b06dda0dfdbd18a1509f7eb34 extends Twig_Template
{
    public function display(array \$context)
    {
        \$this->checkSecurity();
        echo "foo";
    }

    protected function checkSecurity() {
        \$this->env->getExtension('sandbox')->checkSecurity(
            array('upper'),
            array('for')
        );
    }

}
EOF
        , $twig);

        $body = new Twig_Node_Text('foo', 0);
        $extends = new Twig_Node_Expression_Constant('layout.twig', 0);
        $blocks = new Twig_Node();
        $macros = new Twig_Node();
        $filename = 'foo.twig';

        $node = new Twig_Node_Module($body, $extends, $blocks, $macros, $filename);
        $node = new Twig_Node_SandboxedModule($node, array('for'), array('upper'));

        $tests[] = array($node, <<<EOF
<?php

/* foo.twig */
class __TwigTemplate_be925a7b06dda0dfdbd18a1509f7eb34 extends Twig_Template
{
    protected \$parent;

    public function display(array \$context)
    {
        if (null === \$this->parent) {
            \$this->parent = clone \$this->env->loadTemplate("layout.twig");
            \$this->parent->pushBlocks(\$this->blocks);
        }
        \$this->parent->display(\$context);
    }

    protected function checkSecurity() {
        \$this->env->getExtension('sandbox')->checkSecurity(
            array('upper'),
            array('for')
        );

        \$this->parent->checkSecurity();
    }

}
EOF
        , $twig);

        return $tests;
    }
}
