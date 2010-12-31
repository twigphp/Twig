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

/**
 * Default base class for compiled templates.
 *
 * @package twig
 * @author  Fabien Potencier <fabien.potencier@symfony-project.com>
 */
abstract class Twig_Template implements Twig_TemplateInterface
{
    static protected $cache = array();

    protected $env;
    protected $blocks;

    /**
     * Constructor.
     *
     * @param Twig_Environment $env A Twig_Environment instance
     */
    public function __construct(Twig_Environment $env)
    {
        $this->env = $env;
        $this->blocks = array();
    }

    /**
     * Returns the template name.
     *
     * @return string The template name
     */
    public function getTemplateName()
    {
        return null;
    }

    /**
     * Returns the Twig environment.
     *
     * @return Twig_Environment The Twig environment
     */
    public function getEnvironment()
    {
        return $this->env;
    }

    /**
     * Returns the parent template.
     *
     * @return Twig_TemplateInterface|false The parent template or false if there is no parent
     */
    public function getParent(array $context)
    {
        return false;
    }

    /**
     * Displays a parent block.
     *
     * @param string $name    The block name to display from the parent
     * @param array  $context The context
     * @param array  $blocks  The current set of blocks
     */
    public function displayParentBlock($name, array $context, array $blocks = array())
    {
        if (false !== $parent = $this->getParent($context)) {
            $parent->displayBlock($name, $context, $blocks);
        } else {
            throw new Twig_Error_Runtime('This template has no parent', -1, $this->getTemplateName());
        }
    }

    /**
     * Displays a block.
     *
     * @param string $name    The block name to display
     * @param array  $context The context
     * @param array  $blocks  The current set of blocks
     */
    public function displayBlock($name, array $context, array $blocks = array())
    {
        if (isset($blocks[$name])) {
            $b = $blocks;
            unset($b[$name]);
            call_user_func($blocks[$name], $context, $b);
        } elseif (isset($this->blocks[$name])) {
            call_user_func($this->blocks[$name], $context, $blocks);
        } elseif (false !== $parent = $this->getParent($context)) {
            $parent->displayBlock($name, $context, array_merge($this->blocks, $blocks));
        }
    }

    /**
     * Renders a parent block.
     *
     * @param string $name    The block name to render from the parent
     * @param array  $context The context
     * @param array  $blocks  The current set of blocks
     *
     * @return string The rendered block
     */
    public function renderParentBlock($name, array $context, array $blocks = array())
    {
        ob_start();
        $this->displayParentBlock($name, $context, $blocks);

        return new Twig_Markup(ob_get_clean());
    }

    /**
     * Renders a block.
     *
     * @param string $name    The block name to render
     * @param array  $context The context
     * @param array  $blocks  The current set of blocks
     *
     * @return string The rendered block
     */
    public function renderBlock($name, array $context, array $blocks = array())
    {
        ob_start();
        $this->displayBlock($name, $context, $blocks);

        return new Twig_Markup(ob_get_clean());
    }

    /**
     * Returns whether a block exists or not.
     *
     * @param string $name The block name
     *
     * @return Boolean true if the block exists, false otherwise
     */
    public function hasBlock($name)
    {
        return isset($this->blocks[$name]);
    }

    /**
     * Returns all block names.
     *
     * @return array An array of block names
     */
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

    /**
     * Returns a variable from the context.
     *
     * @param array   $context The context
     * @param string  $item    The variable to return from the context
     * @param integer $line    The line where the variable is get
     *
     * @param mixed The variable value in the context
     *
     * @throws Twig_Error_Runtime if the variable does not exist
     */
    protected function getContext($context, $item, $line = -1)
    {
        if (!array_key_exists($item, $context)) {
            throw new Twig_Error_Runtime(sprintf('Variable "%s" does not exist', $item), $line, $this->getTemplateName());
        }

        return $context[$item];
    }

    /**
     * Returns the attribute value for a given array/object.
     *
     * @param mixed   $object        The object or array from where to get the item
     * @param mixed   $item          The item to get from the array or object
     * @param array   $arguments     An array of arguments to pass if the item is an object method
     * @param integer $type          The type of attribute (@see Twig_Node_Expression_GetAttr)
     * @param Boolean $noStrictCheck Whether to throw an exception if the item does not exist ot not
     */
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

                if (is_object($object)) {
                    throw new Twig_Error_Runtime(sprintf('Key "%s" in object (with ArrayAccess) of type "%s" does not exist', $item, get_class($object)), -1, $this->getTemplateName());
                // array
                } else {
                    throw new Twig_Error_Runtime(sprintf('Key "%s" for array with keys "%s" does not exist', $item, implode(', ', array_keys($object))), -1, $this->getTemplateName());
                }
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
