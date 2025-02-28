<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Error;

use Twig\Source;
use Twig\Template;

/**
 * Twig base exception.
 *
 * This exception class and its children must only be used when
 * an error occurs during the loading of a template, when a syntax error
 * is detected in a template, or when rendering a template. Other
 * errors must use regular PHP exception classes (like when the template
 * cache directory is not writable for instance).
 *
 * To help debugging template issues, this class tracks the original template
 * name and line where the error occurred.
 *
 * Whenever possible, you must set these information (original template name
 * and line number) yourself by passing them to the constructor. If some or all
 * these information are not available from where you throw the exception, then
 * this class will guess them automatically.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Error extends \Exception
{
    private $lineno;
    private $rawMessage;
    private ?Source $source;
    private string $phpFile;
    private int $phpLine;

    /**
     * Constructor.
     *
     * By default, automatic guessing is enabled.
     *
     * @param string      $message The error message
     * @param int         $lineno  The template line where the error occurred
     * @param Source|null $source  The source context where the error occurred
     */
    public function __construct(string $message, int $lineno = -1, ?Source $source = null, ?\Throwable $previous = null)
    {
        parent::__construct('', 0, $previous);

        $this->phpFile = $this->getFile();
        $this->phpLine = $this->getLine();
        $this->lineno = $lineno;
        $this->source = $source;
        $this->rawMessage = $message;
        $this->updateRepr();
    }

    public function getRawMessage(): string
    {
        return $this->rawMessage;
    }

    public function getTemplateLine(): int
    {
        return $this->lineno;
    }

    public function setTemplateLine(int $lineno): void
    {
        $this->lineno = $lineno;
        $this->updateRepr();
    }

    public function getSourceContext(): ?Source
    {
        return $this->source;
    }

    public function setSourceContext(?Source $source = null): void
    {
        $this->source = $source;
        $this->updateRepr();
    }

    public function guess(): void
    {
        if ($this->lineno > -1) {
            return;
        }

        $this->guessTemplateInfo();
        $this->updateRepr();
    }

    public function appendMessage($rawMessage): void
    {
        $this->rawMessage .= $rawMessage;
        $this->updateRepr();
    }

    private function updateRepr(): void
    {
        if ($this->source && $this->source->getPath()) {
            // we only update the file and the line together
            $this->file = $this->source->getPath();
            if ($this->lineno > 0) {
                $this->line = $this->lineno;
            } else {
                $this->line = -1;
            }
        }

        $this->message = $this->rawMessage;
        $last = substr($this->message, -1);
        if ($punctuation = '.' === $last || '?' === $last ? $last : '') {
            $this->message = substr($this->message, 0, -1);
        }
        if ($this->source && $this->source->getName()) {
            $this->message .= \sprintf(' in "%s"', $this->source->getName());
        }
        if ($this->lineno > 0) {
            $this->message .= \sprintf(' at line %d', $this->lineno);
        }
        if ($punctuation) {
            $this->message .= $punctuation;
        }
    }

    private function guessTemplateInfo(): void
    {
        // $this->source is never null here (see guess() usage in Template)

        $this->lineno = 0;
        $template = null;
        $backtrace = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS | \DEBUG_BACKTRACE_PROVIDE_OBJECT);
        foreach ($backtrace as $trace) {
            if (isset($trace['object']) && $trace['object'] instanceof Template && $this->source->getName() === $trace['object']->getTemplateName()) {
                $template = $trace['object'];

                break;
            }
        }

        $r = new \ReflectionObject($template);
        $file = $r->getFileName();

        $exceptions = [$e = $this];
        while ($e = $e->getPrevious()) {
            $exceptions[] = $e;
        }

        while ($e = array_pop($exceptions)) {
            $traces = $e->getTrace();
            array_unshift($traces, ['file' => $e instanceof Error ? $e->phpFile : $e->getFile(), 'line' => $e instanceof Error ? $e->phpLine : $e->getLine()]);
            while ($trace = array_shift($traces)) {
                if (!isset($trace['file']) || !isset($trace['line']) || $file != $trace['file']) {
                    continue;
                }

                foreach ($template->getDebugInfo() as $codeLine => $templateLine) {
                    if ($codeLine <= $trace['line']) {
                        // update template line
                        $this->lineno = $templateLine;

                        return;
                    }
                }
            }
        }
    }
}
