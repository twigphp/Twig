<?php

/* include.twig */
class __TwigTemplate_592891f535cbe76515fe11ddba1c56f7 extends Twig_Template
{
    public function display(array $context, array $blocks = array())
    {
        try {
            $context = array_merge($this->env->getGlobals(), $context);

            $line = 1;
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'foo', '1'), "bar", array("foo", ), "method", false, 1), "html");
        } catch (Exception $e) {
            $this->handleException($e, isset($line) ? $line : -1);
        }
    }

    public function getTemplateName()
    {
        return "include.twig";
    }
}
