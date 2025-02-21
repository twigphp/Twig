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
 * this class will guess them automatically (when the line number is set to -1
 * and/or the name is set to null). As this is a costly operation, this
 * can be disabled by passing false for both the name and the line number
 * when creating a new instance of this class.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Error extends \Exception
{
    private int $lineno;
    private string $rawMessage;
    private ?Source $source;

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
        $this->message = $this->rawMessage;

        if ($this->source && $this->source->getPath() && $this->lineno > 0) {
            $this->file = $this->source->getPath();
            $this->line = $this->lineno;

            return;
        }

        $dot = false;
        if (str_ends_with($this->message, '.')) {
            $this->message = substr($this->message, 0, -1);
            $dot = true;
        }

        $questionMark = false;
        if (str_ends_with($this->message, '?')) {
            $this->message = substr($this->message, 0, -1);
            $questionMark = true;
        }

        if ($this->source && $this->source->getName()) {
            $this->message .= \sprintf(' in "%s"', $this->source->getName());
        }

        if ($this->lineno && $this->lineno >= 0) {
            $this->message .= \sprintf(' at line %d', $this->lineno);
        }

        if ($dot) {
            $this->message .= '.';
        }

        if ($questionMark) {
            $this->message .= '?';
        }
    }

    private function guessTemplateInfo(): void
    {
        // $this->source is never null here (see guess() usage in Template)

        $template = null;
        $templateClass = null;
        $backtrace = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS | \DEBUG_BACKTRACE_PROVIDE_OBJECT);
        foreach ($backtrace as $trace) {
            if (isset($trace['object']) && $trace['object'] instanceof Template) {
                $currentClass = $trace['object']::class;
                $isEmbedContainer = null === $templateClass ? false : str_starts_with($templateClass, $currentClass);
                if ($this->source->getName() === $trace['object']->getTemplateName() && !$isEmbedContainer) {
                    $template = $trace['object'];
                    $templateClass = $trace['object']::class;
                }
            }
        }

        if ($template) {
            $this->source = $template->getSourceContext();
        } elseif ($this->lineno > -1) {
            return;
        }

        $r = new \ReflectionObject($template);
        $file = $r->getFileName();

        $exceptions = [$e = $this];
        while ($e = $e->getPrevious()) {
            $exceptions[] = $e;
        }

        while ($e = array_pop($exceptions)) {
            $traces = $e->getTrace();
            array_unshift($traces, ['file' => $e->getFile(), 'line' => $e->getLine()]);

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
