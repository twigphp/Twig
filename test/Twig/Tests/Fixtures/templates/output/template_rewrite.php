<?php

/* template.twig */
class __TwigTemplate_586b90e67fabb8fefd15b6aab5afa69b extends Twig_Template
{
    protected $parent;

    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->blocks = array(
            'foo' => array($this, 'block_foo'),
        );
    }

    public function getParent(array $context)
    {
        if (null === $this->parent) {
            $this->parent = $this->env->loadTemplate("base.twig");
        }

        return $this->parent;
    }

    public function display(array $context, array $blocks = array())
    {
        try {
            $context = array_merge($this->env->getGlobals(), $context);

            $line = 13;
            $this->getParent($context)->display($context, array_merge($this->blocks, $blocks));
        } catch (Exception $e) {
            $this->handleException($e, isset($line) ? $line : -1);
        }
    }

    public function block_foo($context, array $blocks = array())
    {
        try {
            $line = 3;
            $line = 4;
            echo "  ";
            $context['_parent'] = (array) $context;
            $context['_seq'] = twig_ensure_traversable(range(1, 5));
            foreach ($context['_seq'] as $context['_key'] => $context['i']) {
                $line = 5;
                echo "    ";
                echo twig_escape_filter($this->env, $this->getContext($context, 'i', '5'), "html");
                echo "
  ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['i'], $context['_parent'], $context['loop']);
            $context = array_merge($_parent, array_intersect_key($context, $_parent));
            $line = 7;
            echo "
  ";
            $line = 8;
            if ($this->getContext($context, 'bar', '8')) {
                $line = 9;
                echo "  foo
  ";
            }
        } catch (Exception $e) {
            $this->handleException($e, isset($line) ? $line : -1);
        }
    }

    public function getTemplateName()
    {
        return "template.twig";
    }
}
