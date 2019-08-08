<?php

namespace Twig\Tests\Node;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Environment;
use Twig\Node\Expression\AssignNameExpression;
use Twig\Node\Expression\ConditionalExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\ImportNode;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\Node\SetNode;
use Twig\Node\TextNode;
use Twig\Source;
use Twig\Test\NodeTestCase;

class ModuleTest extends NodeTestCase
{
    public function testConstructor()
    {
        $body = new TextNode('foo', 1);
        $parent = new ConstantExpression('layout.twig', 1);
        $blocks = new Node();
        $macros = new Node();
        $traits = new Node();
        $source = new Source('{{ foo }}', 'foo.twig');
        $node = new ModuleNode($body, $parent, $blocks, $macros, $traits, new Node([]), $source);

        $this->assertEquals($body, $node->getNode('body'));
        $this->assertEquals($blocks, $node->getNode('blocks'));
        $this->assertEquals($macros, $node->getNode('macros'));
        $this->assertEquals($parent, $node->getNode('parent'));
        $this->assertEquals($source->getName(), $node->getTemplateName());
    }

    public function getTests()
    {
        $twig = new Environment($this->createMock('\Twig\Loader\LoaderInterface'));

        $tests = [];

        $body = new TextNode('foo', 1);
        $extends = null;
        $blocks = new Node();
        $macros = new Node();
        $traits = new Node();
        $source = new Source('{{ foo }}', 'foo.twig');

        $node = new ModuleNode($body, $extends, $blocks, $macros, $traits, new Node([]), $source);
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
class __TwigTemplate_%x extends \Twig\Template
{
    public function __construct(Environment \$env)
    {
        parent::__construct(\$env);

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
        return array (  30 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return \$this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("", "foo.twig", "");
    }
}
EOF
        , $twig, true];

        $import = new ImportNode(new ConstantExpression('foo.twig', 1), new AssignNameExpression('macro', 1), 2);

        $body = new Node([$import]);
        $extends = new ConstantExpression('layout.twig', 1);

        $node = new ModuleNode($body, $extends, $blocks, $macros, $traits, new Node([]), $source);
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
class __TwigTemplate_%x extends \Twig\Template
{
    public function __construct(Environment \$env)
    {
        parent::__construct(\$env);

        \$this->blocks = [
        ];
    }

    protected function doGetParent(array \$context)
    {
        // line 1
        return "layout.twig";
    }

    protected function doDisplay(array \$context, array \$blocks = [])
    {
        // line 2
        \$context["macro"] = \$this->loadTemplate("foo.twig", "foo.twig", 2)->unwrap();
        // line 1
        \$this->parent = \$this->loadTemplate("layout.twig", "foo.twig", 1);
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
        return array (  36 => 1,  34 => 2,  28 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return \$this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("", "foo.twig", "");
    }
}
EOF
        , $twig, true];

        $set = new SetNode(false, new Node([new AssignNameExpression('foo', 4)]), new Node([new ConstantExpression('foo', 4)]), 4);
        $body = new Node([$set]);
        $extends = new ConditionalExpression(
                        new ConstantExpression(true, 2),
                        new ConstantExpression('foo', 2),
                        new ConstantExpression('foo', 2),
                        2
                    );

        $twig = new Environment($this->createMock('\Twig\Loader\LoaderInterface'), ['debug' => true]);
        $node = new ModuleNode($body, $extends, $blocks, $macros, $traits, new Node([]), $source);
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
class __TwigTemplate_%x extends \Twig\Template
{
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
        return array (  28 => 2,  26 => 4,  20 => 2,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return \$this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("{{ foo }}", "foo.twig", "");
    }
}
EOF
        , $twig, true];

        return $tests;
    }
}
