<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* user.html.twig */
class __TwigTemplate_767d3b3faf3da245e2a0ec328ec3095a6a3018dae112f85032fcf2cef9be89b6 extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'content' => [$this, 'block_content'],
        ];
    }

    protected function doGetParent(array $context)
    {
        // line 1
        return "layout.html.twig";
    }

    protected function doRender(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        $content = '';
        $this->parent = $this->loadTemplate("layout.html.twig", "user.html.twig", 1);
        $content .= $this->parent->render($context, array_merge($this->blocks, $blocks));
        return $content;
    }

    // line 3
    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
        $content = '';
        // line 4
        $content .="    <Content>
    ";
        // line 5
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(twig_get_attribute($this->env, $this->source, ($context["account"] ?? null), "things", [], "any", false, false, false, 5));
        foreach ($context['_seq'] as $context["_key"] => $context["something"]) {
            // line 6
            $content .="        <Thing />
    ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['something'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 8
        $content .="    </Content>
";
        return $content;
    }

    public function getTemplateName()
    {
        return "user.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  67 => 8,  60 => 6,  56 => 5,  53 => 4,  48 => 3,  35 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "user.html.twig", "/home/azjezz/Projects/Twig/example/templates/user.html.twig");
    }
}
