<?php

/*
 * This file is part of Twig.
 *
 * (c) 2011 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_Extension_Debug extends Twig_Extension
{
    private $enableInfo;
    private $format;
    private $formats;

    /**
     * Constructor.
     *
     * Available options:
     *
     *  * enable_info: Whether to display template information (default to false).
     *
     *  * format: Format used for displaying information templates (default: 'html')
     *
     *  * formats: Formats available for formating:
     *               * 'html': '<!-- %1$s -->%3$s%2$s%3$s<!-- // %1$s -->%3$s'
     *               * 'js': '// %1$s%3$s%2$s%3$s'
     *               * 'css': '\/\* %1$s \*\/%3$s%2$s%3$s'
     *
     * @param array $options
     */
    public function __construct(array $options = null)
    {
        // BC
        if (!is_array($options)) {
            $options = array();
        }

        $options = array_merge(array(
            'enable_info'   => false,
            'format'        => 'html',
            'formats'       => array(
                'html'      => '<!-- %1$s -->%3$s%2$s%3$s<!-- // %1$s -->%3$s',
                'js'        => '// %1$s%3$s%2$s%3$s',
                'css'       => '/* %1$s */%3$s%2$s%3$s',
            ),
        ), $options);

        $this->enableInfo   = $options['enable_info'];
        $this->format       = $options['format'];
        $this->formats      = $options['formats'];
    }

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions()
    {
        // dump is safe if var_dump is overridden by xdebug
        $isDumpOutputHtmlSafe = extension_loaded('xdebug')
            // false means that it was not set (and the default is on) or it explicitly enabled
            && (false === ini_get('xdebug.overload_var_dump') || ini_get('xdebug.overload_var_dump'))
            // false means that it was not set (and the default is on) or it explicitly enabled
            // xdebug.overload_var_dump produces HTML only when html_errors is also enabled
            && (false === ini_get('html_errors') || ini_get('html_errors'))
            || 'cli' === php_sapi_name()
        ;

        return array(
            new Twig_SimpleFunction('dump', 'twig_var_dump', array('is_safe' => $isDumpOutputHtmlSafe ? array('html') : array(), 'needs_context' => true, 'needs_environment' => true)),
        );
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'debug';
    }

    /**
     * Enables display of information alogn side template rendering
     */
    public function enableInfo()
    {
        $this->enableInfo = true;
    }

    /**
     * Disables display of information alogn side template rendering
     */
    public function disableInfo()
    {
        $this->enableInfo = false;
    }

    /**
     * Returns whether it's enabled to display template information
     *
     * @return bool
     */
    public function isEnabledInfo()
    {
        return $this->enableInfo;
    }

    /**
     * Sets the string format used to display information
     *
     * @param string|callable $format
     */
    public function setFormat($format)
    {
        $this->format = $format;
    }

    /**
     * Returns the current string format used to display information
     *
     * @return string|callable
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Returns the list of available format strings
     *
     * @return array
     */
    public function getFormats()
    {
        return $this->formats;
    }

    /**
     * Sets the list of available format strings
     *
     * @param array $formats
     */
    public function setFormats(array $formats)
    {
        $this->formats = $formats;
    }

    /**
     * Adds a format string
     *
     * @param $key     Format to use the format string with
     * @param $format  Format string
     */
    public function addFormat($key, $format)
    {
        $this->formats[$key] = $format;
    }

    /**
     * Removes a given format from the format available list
     *
     * @param string $key Format to remove
     */
    public function removeFormat($key)
    {
        unset($this->formats[$key]);
    }

    /**
     * Returns the format string used for this template.
     *
     * If no format string is found for the current `format` then the default formating is used.
     *
     * @param string $filename  Optional filename when using a callback $format
     * @param string $fallback  Optional fallback format
     *
     * @return string
     */
    public function getFormatString($filename = null, $fallback = '%2$s')
    {
        $format = $this->format;
        if (is_callable($this->format)) {
            $format = call_user_func($this->format, $filename);
        }

        return isset($this->formats[$format]) ? $this->formats[$format] : $fallback;
    }
}

function twig_var_dump(Twig_Environment $env, $context)
{
    if (!$env->isDebug()) {
        return;
    }

    ob_start();

    $count = func_num_args();
    if (2 === $count) {
        $vars = array();
        foreach ($context as $key => $value) {
            if (!$value instanceof Twig_Template) {
                $vars[$key] = $value;
            }
        }

        var_dump($vars);
    } else {
        for ($i = 2; $i < $count; $i++) {
            var_dump(func_get_arg($i));
        }
    }

    return ob_get_clean();
}
