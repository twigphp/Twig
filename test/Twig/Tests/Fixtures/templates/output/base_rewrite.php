<?php

/* base.twig */
class __TwigTemplate_84cc65a0fbbf82f7e4aa70f25a24f0b4 extends Twig_Template
{
    public function display(array $context, array $blocks = array())
    {
        try {
            $context = array_merge($this->env->getGlobals(), $context);

            $line = 4;
            echo "
";
            $line = 5;
            echo twig_escape_filter($this->env, $this->getAttribute($this, "foo", array(), "method", false, 5), "html");
            echo "
";
            $line = 6;
            $this->env->loadTemplate("include.twig")->display($context);
        } catch (Exception $e) {
            $this->handleException($e, isset($line) ? $line : -1);
        }
    }

    public function getfoo($bar = null)
    {
        try {
            $line = 1;
            $context = array_merge($this->env->getGlobals(), array(
                "bar" => $bar,
            ));

            ob_start();
            $line = 2;
            echo "    ";
            echo twig_escape_filter($this->env, ((twig_test_defined("bar", $context)) ? (twig_default_filter($this->getContext($context, 'bar', '2'), "bar")) : ("bar")), "html");
            echo "
";

            return ob_get_clean();
        } catch (Exception $e) {
            $this->handleException($e, isset($line) ? $line : -1);
        }
    }

    public function getTemplateName()
    {
        return "base.twig";
    }
}
