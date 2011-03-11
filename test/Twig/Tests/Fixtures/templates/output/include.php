<?php

/* include.twig */
class __TwigTemplate_592891f535cbe76515fe11ddba1c56f7 extends Twig_Template
{
    public function display(array $context, array $blocks = array())
    {
        $context = array_merge($this->env->getGlobals(), $context);

        $line = 1;
        echo twig_escape_filter($this->env, $this->getAttribute((isset($context['foo']) ? $context['foo'] : null), "bar", array("foo", ), "method", false, 1), "html");
    }

    public function getTemplateName()
    {
        return "include.twig";
    }
}
