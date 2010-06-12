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
abstract class Twig_Template implements Twig_TemplateInterface
{
    protected $env;
    protected $cache;
    protected $blocks;

    public function __construct(Twig_Environment $env)
    {
        $this->env = $env;
        $this->cache = array();
        $this->blocks = array();
    }

    public function getEnvironment()
    {
        return $this->env;
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

    protected function getContext($context, $item)
    {
        if (!array_key_exists($item, $context)) {
            throw new InvalidArgumentException(sprintf('Variable "%s" does not exist.', $item));
        }

        return $context[$item];
    }

    protected function getAttribute($object, $item, array $arguments = array(), $type = Twig_Node_Expression_GetAttr::TYPE_ANY)
    {
        // array
        if (Twig_Node_Expression_GetAttr::TYPE_METHOD !== $type) {
            if ((is_array($object) || is_object($object) && $object instanceof ArrayAccess) && isset($object[$item])) {
                return $object[$item];
            }

            if (Twig_Node_Expression_GetAttr::TYPE_ARRAY === $type) {
                if (!$this->env->isStrictVariables()) {
                    return null;
                }

                throw new InvalidArgumentException(sprintf('Key "%s" for array "%s" does not exist.', $item, $object));
            }
        }

        if (!is_object($object)) {
            if (!$this->env->isStrictVariables()) {
                return null;
            }

            throw new InvalidArgumentException(sprintf('Item "%s" for "%s" does not exist.', $item, $object));
        }

        // object property
        if (Twig_Node_Expression_GetAttr::TYPE_METHOD !== $type) {
            if (property_exists($object, $item)) {
                if ($this->env->hasExtension('sandbox')) {
                    $this->env->getExtension('sandbox')->checkPropertyAllowed($object, $item);
                }

                return $object->$item;
            }
        }

        // object method
        $class = get_class($object);

        if (!isset($this->cache[$class])) {
            $r = new ReflectionClass($class);
            $this->cache[$class] = array();
            foreach ($r->getMethods(ReflectionMethod::IS_STATIC | ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_FINAL) as $method) {
                $this->cache[$class][strtolower($method->getName())] = true;
            }
        }

        $item = strtolower($item);
        if (isset($this->cache[$class][$item])) {
            $method = $item;
        } elseif (isset($this->cache[$class]['get'.$item])) {
            $method = 'get'.$item;
        } elseif (isset($this->cache[$class]['__call'])) {
            $method = $item;
        } else {
            if (!$this->env->isStrictVariables()) {
                return null;
            }

            throw new InvalidArgumentException(sprintf('Method "%s" for object "%s" does not exist.', $item, get_class($object)));
        }

        if ($this->env->hasExtension('sandbox')) {
            $this->env->getExtension('sandbox')->checkMethodAllowed($object, $method);
        }

        return call_user_func_array(array($object, $method), $arguments);
    }
}
