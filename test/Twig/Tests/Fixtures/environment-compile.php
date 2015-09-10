<?php

/* index */
class __TwigTemplate_e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855 extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = array(
        );
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        // line 1
        echo twig_escape_filter($this->env, (isset($context["foo"]) ? $context["foo"] : null), "html", null, true);
        echo "
";
        // line 2
        echo twig_escape_filter($this->env, (isset($context["bar"]) ? $context["bar"] : null), "html", null, true);
        echo "
";
    }

    public function getTemplateName()
    {
        return "index";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  23 => 2,  19 => 1,);
    }
}
// {{ foo }}
// {{ bar }}
// 
