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
            list($lineno, $filename) = $this->findTemplateInfo(null !== $previous ? $previous : $this, $lineno, $filename);
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
            $this->message .= sprintf(' in %s', json_encode($this->filename));
        }

        if ($this->lineno >= 0) {
            $this->message .= sprintf(' at line %d', $this->lineno);
        }

        if ($dot) {
            $this->message .= '.';
        }
    }

    protected function findTemplateInfo(Exception $e, $currentLine, $currentFile)
    {
        if (!function_exists('token_get_all')) {
            return array($currentLine, $currentFile);
        }

        $traces = $e->getTrace();
        foreach ($traces as $i => $trace) {
            if (!isset($trace['class']) || 'Twig_Template' === $trace['class']) {
                continue;
            }

            $r = new ReflectionClass($trace['class']);
            if (!$r->implementsInterface('Twig_TemplateInterface')) {
                continue;
            }

            if (!file_exists($r->getFilename())) {
                // probably an eval()'d code
                return array($currentLine, $currentFile);
            }

            if (0 === $i) {
                $line = $e->getLine();
            } else {
                $line = isset($traces[$i - 1]['line']) ? $traces[$i - 1]['line'] : -log(0);
            }

            $tokens = token_get_all(file_get_contents($r->getFilename()));
            $templateline = -1;
            $template = null;
            while ($token = array_shift($tokens)) {
                if (isset($token[2]) && $token[2] >= $line) {
                    return array($templateline, $template);
                }

                if (T_COMMENT === $token[0] && null === $template && preg_match('#/\* +(.+) +\*/#', $token[1], $match)) {
                    $template = $match[1];
                } elseif (T_COMMENT === $token[0] && preg_match('#^//\s*line (\d+)\s*$#', $token[1], $match)) {
                    $templateline = $match[1];
                }
            }

            return array($currentLine, $template);
        }

        return array($currentLine, $currentFile);
    }
}
