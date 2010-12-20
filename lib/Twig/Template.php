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
    static protected $cache = array();

    protected $env;
    protected $blocks;

    public function __construct(Twig_Environment $env)
    {
        $this->env = $env;
        $this->blocks = array();
    }

    public function getTemplateName()
    {
        return null;
    }

    public function getEnvironment()
    {
        return $this->env;
    }

    public function getParent(array $context)
    {
        return false;
    }

    public function getParentBlock($name, array $context, array $blocks = array())
    {
        if (false !== $parent = $this->getParent($context)) {
            return $parent->getBlock($name, $context, $blocks);
        } else {
            throw new Twig_Error_Runtime('This template has no parent', -1, $this->getTemplateName());
        }
    }

    public function getBlock($name, array $context, array $blocks = array())
    {
        if (isset($blocks[$name])) {
            $b = $blocks;
            unset($b[$name]);
            return call_user_func($blocks[$name], $context, $b);
        } elseif (isset($this->blocks[$name])) {
            return call_user_func($this->blocks[$name], $context, $blocks);
        }

        if (false !== $parent = $this->getParent($context)) {
            return $parent->getBlock($name, $context, array_merge($this->blocks, $blocks));
        }
    }

    public function hasBlock($name)
    {
        return isset($this->blocks[$name]);
    }

    public function getBlockNames()
    {
        return array_keys($this->blocks);
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

    protected function getContext($context, $item, $line = -1)
    {
        if (!array_key_exists($item, $context)) {
            throw new Twig_Error_Runtime(sprintf('Variable "%s" does not exist', $item), $line, $this->getTemplateName());
        }

        return $context[$item];
    }

    protected function callFunction($context, $function, array $arguments = array())
    {
        if (!$function instanceof Twig_Function) {
            throw new Twig_Error_Runtime('Called a non-function', -1, $this->getTemplateName());
        }

        $object = $function->getObject();
        if (!is_object($object)) {
            $object = $this->getContext($context, $object);
        }

        return $this->getAttribute($object, $function->getMethod(), $arguments, Twig_Node_Expression_GetAttr::TYPE_METHOD, false);
    }

    protected function getAttribute($object, $item, array $arguments = array(), $type = Twig_Node_Expression_GetAttr::TYPE_ANY, $noStrictCheck = false)
    {
        // array
        if (Twig_Node_Expression_GetAttr::TYPE_METHOD !== $type) {
            if ((is_array($object) || is_object($object) && $object instanceof ArrayAccess) && isset($object[$item])) {
                return $object[$item];
            }

            if (Twig_Node_Expression_GetAttr::TYPE_ARRAY === $type) {
                if (!$this->env->isStrictVariables() || $noStrictCheck) {
                    return null;
                }

                throw new Twig_Error_Runtime(sprintf('Key "%s" for array "%s" does not exist', $item, $object), -1, $this->getTemplateName());
            }
        }

        if (!is_object($object)) {
            if (!$this->env->isStrictVariables() || $noStrictCheck) {
                return null;
            }
            throw new Twig_Error_Runtime(sprintf('Item "%s" for "%s" does not exist', $item, $object), -1, $this->getTemplateName());
        }

        // get some information about the object
        $class = get_class($object);
        if (!isset(self::$cache[$class])) {
            $r = new ReflectionClass($class);
            self::$cache[$class] = array('methods' => array(), 'properties' => array());
            foreach ($r->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                self::$cache[$class]['methods'][strtolower($method->getName())] = true;
            }

            foreach ($r->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
                self::$cache[$class]['properties'][strtolower($property->getName())] = true;
            }
        }

        // object property
        if (Twig_Node_Expression_GetAttr::TYPE_METHOD !== $type) {
            if (isset(self::$cache[$class]['properties'][strtolower($item)]) || isset($object->$item)) {
                if ($this->env->hasExtension('sandbox')) {
                    $this->env->getExtension('sandbox')->checkPropertyAllowed($object, $item);
                }

                return $object->$item;
            }
        }

        // object method
        $lcItem = strtolower($item);
        if (isset(self::$cache[$class]['methods'][$lcItem])) {
            $method = $item;
        } elseif (isset(self::$cache[$class]['methods']['get'.$lcItem])) {
            $method = 'get'.$item;
        } elseif (isset(self::$cache[$class]['methods']['is'.$lcItem])) {
            $method = 'is'.$item;
        } elseif (isset(self::$cache[$class]['methods']['__call'])) {
            $method = $item;
        } else {
            if (!$this->env->isStrictVariables() || $noStrictCheck) {
                return null;
            }

            throw new Twig_Error_Runtime(sprintf('Method "%s" for object "%s" does not exist', $item, get_class($object)), -1, $this->getTemplateName());
        }

        if ($this->env->hasExtension('sandbox')) {
            $this->env->getExtension('sandbox')->checkMethodAllowed($object, $method);
        }

        $ret = call_user_func_array(array($object, $method), $arguments);

        if ($object instanceof Twig_TemplateInterface) {
            return new Twig_Markup($ret);
        }

        return $ret;
    }
}
