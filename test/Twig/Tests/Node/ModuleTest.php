<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Node_ModuleTest extends \Twig\Test\NodeTestCase
{
    public function testConstructor()
    {
        $body = new \Twig\Node\TextNode('foo', 1);
        $parent = new \Twig\Node\Expression\ConstantExpression('layout.twig', 1);
        $blocks = new \Twig\Node\Node();
        $macros = new \Twig\Node\Node();
        $traits = new \Twig\Node\Node();
        $source = new \Twig\Source('{{ foo }}', 'foo.twig');
        $node = new \Twig\Node\ModuleNode($body, $parent, $blocks, $macros, $traits, new \Twig\Node\Node([]), $source);

        $this->assertEquals($body, $node->getNode('body'));
        $this->assertEquals($blocks, $node->getNode('blocks'));
        $this->assertEquals($macros, $node->getNode('macros'));
        $this->assertEquals($parent, $node->getNode('parent'));
        $this->assertEquals($source->getName(), $node->getTemplateName());
    }

    public function getTests()
    {
        $twig = new \Twig\Environment($this->getMockBuilder(\Twig\Loader\LoaderInterface::class)->getMock());

        $tests = [];

        $body = new \Twig\Node\TextNode('foo', 1);
        $extends = null;
        $blocks = new \Twig\Node\Node();
        $macros = new \Twig\Node\Node();
        $traits = new \Twig\Node\Node();
        $source = new \Twig\Source('{{ foo }}', 'foo.twig');

        $node = new \Twig\Node\ModuleNode($body, $extends, $blocks, $macros, $traits, new \Twig\Node\Node([]), $source);
        $tests[] = [$node, <<<EOF
<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* foo.twig */
class __TwigTemplate_%x extends  \Twig\Template
{
    private \$source;

    public function __construct(\Twig\Environment \$env)
    {
        parent::__construct(\$env);

        \$this->source = \$this->getSourceContext();

        \$this->parent = false;

        \$this->blocks = [
        ];
    }

    protected function doDisplay(array \$context, array \$blocks = [])
    {
        // line 1
        echo "foo";
    }

    public function getTemplateName()
    {
        return "foo.twig";
    }

    public function getDebugInfo()
    {
        return array (  34 => 1,);
    }

    public function getSourceContext()
    {
        return new \Twig\Source("", "foo.twig", "");
    }
}
EOF
        , $twig, true];

        $import = new \Twig\Node\ImportNode(new \Twig\Node\Expression\ConstantExpression('foo.twig', 1), new \Twig\Node\Expression\AssignNameExpression('macro', 1), 2);

        $body = new \Twig\Node\Node([$import]);
        $extends = new \Twig\Node\Expression\ConstantExpression('layout.twig', 1);

        $node = new \Twig\Node\ModuleNode($body, $extends, $blocks, $macros, $traits, new \Twig\Node\Node([]), $source);
        $tests[] = [$node, <<<EOF
<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* foo.twig */
class __TwigTemplate_%x extends  \Twig\Template
{
    private \$source;

    public function __construct(\Twig\Environment \$env)
    {
        parent::__construct(\$env);

        \$this->source = \$this->getSourceContext();

        // line 1
        \$this->parent = \$this->loadTemplate("layout.twig", "foo.twig", 1);
        \$this->blocks = [
        ];
    }

    protected function doGetParent(array \$context)
    {
        return "layout.twig";
    }

    protected function doDisplay(array \$context, array \$blocks = [])
    {
        // line 2
        \$context["macro"] = \$this->loadTemplate("foo.twig", "foo.twig", 2);
        // line 1
        \$this->parent->display(\$context, array_merge(\$this->blocks, \$blocks));
    }

    public function getTemplateName()
    {
        return "foo.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  41 => 1,  39 => 2,  26 => 1,);
    }

    public function getSourceContext()
    {
        return new \Twig\Source("", "foo.twig", "");
    }
}
EOF
        , $twig, true];

        $set = new \Twig\Node\SetNode(false, new \Twig\Node\Node([new \Twig\Node\Expression\AssignNameExpression('foo', 4)]), new \Twig\Node\Node([new \Twig\Node\Expression\ConstantExpression('foo', 4)]), 4);
        $body = new \Twig\Node\Node([$set]);
        $extends = new \Twig\Node\Expression\ConditionalExpression(
                        new \Twig\Node\Expression\ConstantExpression(true, 2),
                        new \Twig\Node\Expression\ConstantExpression('foo', 2),
                        new \Twig\Node\Expression\ConstantExpression('foo', 2),
                        2
                    );

        $twig = new \Twig\Environment($this->getMockBuilder(\Twig\Loader\LoaderInterface::class)->getMock(), ['debug' => true]);
        $node = new \Twig\Node\ModuleNode($body, $extends, $blocks, $macros, $traits, new \Twig\Node\Node([]), $source);
        $tests[] = [$node, <<<EOF
<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* foo.twig */
class __TwigTemplate_%x extends  \Twig\Template
{
    private \$source;

    public function __construct(\Twig\Environment \$env)
    {
        parent::__construct(\$env);

        \$this->source = \$this->getSourceContext();

        \$this->blocks = [
        ];
    }

    protected function doGetParent(array \$context)
    {
        // line 2
        return \$this->loadTemplate(((true) ? ("foo") : ("foo")), "foo.twig", 2);
    }

    protected function doDisplay(array \$context, array \$blocks = [])
    {
        // line 4
        \$context["foo"] = "foo";
        // line 2
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

    public function getDebugInfo()
    {
        return array (  40 => 2,  38 => 4,  32 => 2,);
    }

    public function getSourceContext()
    {
        return new \Twig\Source("{{ foo }}", "foo.twig", "");
    }
}
EOF
        , $twig, true];

        return $tests;
    }
}
