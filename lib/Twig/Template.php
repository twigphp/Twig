<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 * (c) 2009 Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
abstract class Twig_Template extends Twig_Resource implements Twig_TemplateInterface
{
    protected $blocks;

    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->blocks = array();
    }

    protected function getBlock($name, array $context)
    {
        return call_user_func($this->blocks[$name][0], $context, array_slice($this->blocks[$name], 1));
    }

    protected function getParent($context, $parents)
    {
        return call_user_func($parents[0], $context, array_slice($parents, 1));
    }

    public function pushBlocks($blocks)
    {
        foreach ($blocks as $name => $call) {
            if (!isset($this->blocks[$name])) {
                $this->blocks[$name] = array();
            }

            $this->blocks[$name] = array_merge($call, $this->blocks[$name]);
        }
    }

    /**
     * Renders the template with the given context and returns it as string.
     *
     * @param array $context An array of parameters to pass to the template
     *
     * @return string The rendered template
     */
    public function render(array $context)
    {
        ob_start();
        try {
            $this->display($context);
        } catch (Exception $e) {
            ob_end_clean();

            throw $e;
        }

        return ob_get_clean();
    }

    abstract protected function getName();
}
