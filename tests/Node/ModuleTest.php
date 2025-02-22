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
use Twig\Loader\ArrayLoader;
use Twig\Node\BodyNode;
use Twig\Node\EmptyNode;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\Ternary\ConditionalTernary;
use Twig\Node\Expression\Variable\AssignContextVariable;
use Twig\Node\Expression\Variable\AssignTemplateVariable;
use Twig\Node\Expression\Variable\TemplateVariable;
use Twig\Node\ImportNode;
use Twig\Node\ModuleNode;
use Twig\Node\Nodes;
use Twig\Node\SetNode;
use Twig\Node\TextNode;
use Twig\Source;
use Twig\Test\NodeTestCase;

class ModuleTest extends NodeTestCase
{
    public function testConstructor()
    {
        $body = new BodyNode([new TextNode('foo', 1)]);
        $parent = new ConstantExpression('layout.twig', 1);
        $blocks = new EmptyNode();
        $macros = new EmptyNode();
        $traits = new EmptyNode();
        $source = new Source('{{ foo }}', 'foo.twig');
        $node = new ModuleNode($body, $parent, $blocks, $macros, $traits, new EmptyNode(), $source);

        $this->assertEquals($body, $node->getNode('body'));
        $this->assertEquals($blocks, $node->getNode('blocks'));
        $this->assertEquals($macros, $node->getNode('macros'));
        $this->assertEquals($parent, $node->getNode('parent'));
        $this->assertEquals($source->getName(), $node->getTemplateName());
    }

    public static function provideTests(): iterable
    {
        $twig = new Environment(new ArrayLoader(['foo.twig' => '{{ foo }}']));

        $tests = [];

        $body = new BodyNode([new TextNode('foo', 1)]);
        $extends = null;
        $blocks = new EmptyNode();
        $macros = new EmptyNode();
        $traits = new EmptyNode();
        $source = new Source('{{ foo }}', 'foo.twig');

        $node = new ModuleNode($body, $extends, $blocks, $macros, $traits, new EmptyNode(), $source);
        $tests[] = [$node, <<<EOF
<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* foo.twig */
class __TwigTemplate_%x extends Template
{
    private Source \$source;
    /**
     * @var array<string, Template>
     */
    private array \$macros = [];

    public function __construct(Environment \$env)
    {
        parent::__construct(\$env);

        \$this->source = \$this->getSourceContext();

        \$this->parent = false;

        \$this->blocks = [
        ];
    }

    protected function doDisplay(array \$context, array \$blocks = []): iterable
    {
        \$macros = \$this->macros;
        // line 1
        yield "foo";
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "foo.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return array (  42 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "foo.twig", "");
    }
}
EOF
            , $twig, true];

        $import = new ImportNode(new ConstantExpression('foo.twig', 1), new AssignTemplateVariable(new TemplateVariable('macro', 2), true), 2);

        $body = new BodyNode([$import]);
        $extends = new ConstantExpression('layout.twig', 1);

        $node = new ModuleNode($body, $extends, $blocks, $macros, $traits, new EmptyNode(), $source);
        $tests[] = [$node, <<<EOF
<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* foo.twig */
class __TwigTemplate_%x extends Template
{
    private Source \$source;
    /**
     * @var array<string, Template>
     */
    private array \$macros = [];

    public function __construct(Environment \$env)
    {
        parent::__construct(\$env);

        \$this->source = \$this->getSourceContext();

        \$this->blocks = [
        ];
    }

    protected function doGetParent(array \$context): bool|string|Template|TemplateWrapper
    {
        // line 1
        return "layout.twig";
    }

    protected function doDisplay(array \$context, array \$blocks = []): iterable
    {
        \$macros = \$this->macros;
        // line 2
        \$macros["macro"] = \$this->macros["macro"] = \$this->load("foo.twig", 2)->unwrap();
        // line 1
        \$this->parent = \$this->load("layout.twig", 1);
        yield from \$this->parent->unwrap()->yield(\$context, array_merge(\$this->blocks, \$blocks));
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "foo.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return array (  48 => 1,  46 => 2,  39 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "foo.twig", "");
    }
}
EOF
            , $twig, true];

        $set = new SetNode(false, new Nodes([new AssignContextVariable('foo', 4)]), new Nodes([new ConstantExpression('foo', 4)]), 4);
        $body = new BodyNode([$set]);
        $extends = new ConditionalTernary(
            new ConstantExpression(true, 2),
            new ConstantExpression('foo', 2),
            new ConstantExpression('foo', 2),
            2
        );

        $twig = new Environment(new ArrayLoader(['foo.twig' => '{{ foo }}']), ['debug' => true]);
        $node = new ModuleNode($body, $extends, $blocks, $macros, $traits, new EmptyNode(), $source);
        $tests[] = [$node, <<<EOF
<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* foo.twig */
class __TwigTemplate_%x extends Template
{
    private Source \$source;
    /**
     * @var array<string, Template>
     */
    private array \$macros = [];

    public function __construct(Environment \$env)
    {
        parent::__construct(\$env);

        \$this->source = \$this->getSourceContext();

        \$this->blocks = [
        ];
    }

    protected function doGetParent(array \$context): bool|string|Template|TemplateWrapper
    {
        // line 2
        return \$this->load(((true) ? ("foo") : ("foo")), 2);
    }

    protected function doDisplay(array \$context, array \$blocks = []): iterable
    {
        \$macros = \$this->macros;
        // line 4
        \$context["foo"] = "foo";
        // line 2
        yield from \$this->getParent(\$context)->unwrap()->yield(\$context, array_merge(\$this->blocks, \$blocks));
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "foo.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return array (  48 => 2,  46 => 4,  39 => 2,);
    }

    public function getSourceContext(): Source
    {
        return new Source("{{ foo }}", "foo.twig", "");
    }
}
EOF
            , $twig, true];

        return $tests;
    }
}
