<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Twig base exception.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class Twig_Error extends Exception
{
    protected $lineno;
    protected $filename;
    protected $rawMessage;
    protected $previous;

    /**
     * Constructor.
     *
     * @param string    $message  The error message
     * @param integer   $lineno   The template line where the error occurred
     * @param string    $filename The template file name where the error occurred
     * @param Exception $previous The previous exception
     */
    public function __construct($message, $lineno = -1, $filename = null, Exception $previous = null)
    {
        if (-1 === $lineno || null === $filename) {
            list($lineno, $filename) = $this->findTemplateInfo(null !== $previous ? $previous : $this);
        }

        $this->lineno = $lineno;
        $this->filename = $filename;
        $this->rawMessage = $message;

        $this->updateRepr();

        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
            $this->previous = $previous;
            parent::__construct($this->message);
        } else {
            parent::__construct($this->message, 0, $previous);
        }
    }

    /**
     * Gets the filename where the error occurred.
     *
     * @return string The filename
     */
    public function getTemplateFile()
    {
        return $this->filename;
    }

    /**
     * Sets the filename where the error occurred.
     *
     * @param string $filename The filename
     */
    public function setTemplateFile($filename)
    {
        $this->filename = $filename;

        $this->updateRepr();
    }

    /**
     * Gets the template line where the error occurred.
     *
     * @return integer The template line
     */
    public function getTemplateLine()
    {
        return $this->lineno;
    }

    /**
     * Sets the template line where the error occurred.
     *
     * @param integer $lineno The template line
     */
    public function setTemplateLine($lineno)
    {
        $this->lineno = $lineno;

        $this->updateRepr();
    }

    /**
     * For PHP < 5.3.0, provides access to the getPrevious() method.
     *
     * @param  string $method    The method name
     * @param  array  $arguments The parameters to be passed to the method
     *
     * @return Exception The previous exception or null
     */
    public function __call($method, $arguments)
    {
        if ('getprevious' == strtolower($method)) {
            return $this->previous;
        }

        throw new BadMethodCallException(sprintf('Method "Twig_Error::%s()" does not exist.', $method));
    }

    protected function updateRepr()
    {
        $this->message = $this->rawMessage;

        if (null !== $this->filename) {
            $this->message .= sprintf(' in %s', json_encode($this->filename));
        }

        if ($this->lineno >= 0) {
            $this->message .= sprintf(' at line %d', $this->lineno);
        }
    }

    protected function findTemplateInfo(Exception $e)
    {
        if (!function_exists('token_get_all')) {
            return array(-1, null);
        }

        $traces = $e->getTrace();
        foreach ($traces as $i => $trace) {
            if (!isset($trace['class']) || !isset($trace['line']) || 'Twig_Template' === $trace['class']) {
                continue;
            }

            $r = new ReflectionClass($trace['class']);
            if (!$r->implementsInterface('Twig_TemplateInterface')) {
                continue;
            }

            $trace = $traces[$i - 1];

            if (!file_exists($r->getFilename())) {
                // probably an eval()'d code
                return array(-1, null);
            }

            $tokens = token_get_all(file_get_contents($r->getFilename()));
            $currentline = 0;
            $templateline = -1;
            $template = null;
            while ($token = array_shift($tokens)) {
                if (T_WHITESPACE === $token[0]) {
                    $currentline += substr_count($token[1], "\n");
                    if ($currentline >= $trace['line']) {
                        return array($templateline, $template);
                    }
                } elseif (T_COMMENT === $token[0] && null === $template && preg_match('#/\* +(.+) +\*/#', $token[1], $match)) {
                    $template = $match[1];
                } elseif (T_COMMENT === $token[0] && preg_match('#line (\d+)#', $token[1], $match)) {
                    $templateline = $match[1];
                }
            }
        }

        return array(-1, null);
    }
}
