<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
use Twig\Error\RuntimeError;
use Twig\Loader\ArrayLoader;
use Twig\Node\BodyNode;
use Twig\Node\EmptyNode;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\Ternary\ConditionalTernary;
use Twig\Node\Expression\Variable\AssignContextVariable;
use Twig\Node\Expression\Variable\AssignMacroVariable;
use Twig\Node\Expression\Variable\MacroVariable;
use Twig\Node\ImportNode;
use Twig\Node\MacrosNode;
use Twig\Node\ModuleNode;
use Twig\Node\Nodes;
use Twig\Node\SetNode;
use Twig\Node\TextNode;
use Twig\Source;
use Twig\Test\NodeTestCase;

class ModuleTest extends NodeTestCase
{
    public function testConstructor(): void
    {
        $body = new BodyNode([new TextNode('foo', 1)]);
        $parent = new ConstantExpression('layout.twig', 1);
        $blocks = new EmptyNode();
        $macros = new MacrosNode();
        $traits = new EmptyNode();
        $source = new Source('{{ foo }}', 'foo.twig');
        $node = new ModuleNode($body, $parent, $blocks, $macros, $traits, new EmptyNode(), $source);

        $this->assertEquals($body, $node->getNode('body'));
        $this->assertEquals($blocks, $node->getNode('blocks'));
        $this->assertEquals($macros, $node->getNode('macros'));
        $this->assertEquals($parent, $node->getNode('parent'));
        $this->assertEquals($source->getName(), $node->getTemplateName());
    }

    public function testUseTagTemplateNameDoesNotInjectPhpInCompiledOutput(): void
    {
        $evilName = "evil' . print('BAD-EOL') . '.twig";
        $loader = new ArrayLoader([
            $evilName => '{% block existing %}ok{% endblock %}',
            'main.twig' => "{% use \"$evilName\" with absent_block as alias %}",
        ]);
        $twig = new Environment($loader);

        ob_start();
        $message = null;
        try {
            $twig->load('main.twig');
        } catch (RuntimeError $e) {
            $message = $e->getMessage();
        }
        $stdout = ob_get_clean();

        $this->assertSame('', $stdout, 'No code from the template name must execute when the trait is loaded.');
        $this->assertNotNull($message, 'A RuntimeError must be raised for the missing block.');
        $this->assertStringContainsString($evilName, $message, 'The error message must contain the literal template name.');
    }

    public static function provideTests(): iterable
    {
        $twig = new Environment(new ArrayLoader(['foo.twig' => '{{ foo }}']));

        $tests = [];

        $body = new BodyNode([new TextNode('foo', 1)]);
        $extends = null;
        $blocks = new EmptyNode();
        $macros = new MacrosNode();
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
use Twig\MacroNamespace;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityNotAllowedTestError;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* foo.twig */
class __TwigTemplate_%x extends Template
{
    private Source \$source;
    /**
     * @var array<string, MacroNamespace>
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
        return array (  44 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "foo.twig", "");
    }
}
EOF, $twig, true];

        $import = new ImportNode(new ConstantExpression('foo.twig', 1), new AssignMacroVariable(new MacroVariable('macro', 2), true), 2);

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
use Twig\MacroNamespace;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityNotAllowedTestError;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* foo.twig */
class __TwigTemplate_%x extends Template
{
    private Source \$source;
    /**
     * @var array<string, MacroNamespace>
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
        \$macros["macro"] = \$this->macros["macro"] = \$this->load("foo.twig", 2)->unwrap()->getMacroNamespace();
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
        return array (  50 => 1,  48 => 2,  41 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "foo.twig", "");
    }
}
EOF, $twig, true];

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
use Twig\MacroNamespace;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityNotAllowedTestError;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* foo.twig */
class __TwigTemplate_%x extends Template
{
    private Source \$source;
    /**
     * @var array<string, MacroNamespace>
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
        return array (  50 => 2,  48 => 4,  41 => 2,);
    }

    public function getSourceContext(): Source
    {
        return new Source("{{ foo }}", "foo.twig", "");
    }
}
EOF, $twig, true];

        return $tests;
    }
}
