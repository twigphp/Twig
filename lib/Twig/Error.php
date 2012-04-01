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
 * @author     Fabien Potencier <fabien@symfony.com>
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
            if ($trace = $this->getTemplateTrace()) {
                if (-1 === $lineno) {
                    $lineno = $this->guessTemplateLine($trace);
                }

                if (null === $filename) {
                    $filename = $trace['object']->getTemplateName();
                }
            }
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
     * Gets the raw message.
     *
     * @return string The raw message
     */
    public function getRawMessage()
    {
        return $this->rawMessage;
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

        $dot = false;
        if ('.' === substr($this->message, -1)) {
            $this->message = substr($this->message, 0, -1);
            $dot = true;
        }

        if (null !== $this->filename) {
            $this->message .= sprintf(' in %s', is_string($this->filename) ? '"'.$this->filename.'"' : json_encode($this->filename));
        }

        if ($this->lineno >= 0) {
            $this->message .= sprintf(' at line %d', $this->lineno);
        }

        if ($dot) {
            $this->message .= '.';
        }
    }

    protected function getTemplateTrace()
    {
        foreach (debug_backtrace() as $trace) {
            if (isset($trace['object']) && $trace['object'] instanceof Twig_Template) {
                return $trace;
            }
        }
    }

    protected function guessTemplateLine($trace)
    {
        if (isset($trace['line'])) {
            foreach ($trace['object']->getDebugInfo() as $codeLine => $templateLine) {
                if ($codeLine <= $trace['line']) {
                    return $templateLine;
                }
            }
        }

        return -1;
    }
}
