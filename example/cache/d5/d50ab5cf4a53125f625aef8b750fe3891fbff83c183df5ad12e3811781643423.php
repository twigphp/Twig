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

/* layout.html.twig */
class __TwigTemplate_cb71be85d233d3167e83b28319e1477d3231df5ac48b54a2ffe85d288d906f93 extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
            'content' => [$this, 'block_content'],
        ];
    }

    protected function doRender(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        $content = '';
        // line 1
        $content .="<Layout>
    <Greeting>Hello, ";
        // line 2
        $content .=twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context["account"] ?? null), "user", [], "any", false, false, false, 2), "username", [], "any", false, false, false, 2), "html", null, true);
        $content .="</Greeting>
    
    ";
        // line 4
        $content .= $this->renderBlock('content', $context, $blocks);
        // line 5
        $content .="</Layout>
";
        return $content;
    }

    // line 4
    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
        $content = '';
        return $content;
    }

    public function getTemplateName()
    {
        return "layout.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  55 => 4,  49 => 5,  47 => 4,  42 => 2,  39 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "layout.html.twig", "/home/azjezz/Projects/Twig/example/templates/layout.html.twig");
    }
}
