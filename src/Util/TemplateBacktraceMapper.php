<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Util;

use Twig\Template;

/**
 * Replaces the frames of a backtrace that point to a compiled template
 * with the template source file and line.
 */
final class TemplateBacktraceMapper
{
    /**
     * @var array<class-string<Template>, Template|null>
     */
    private array $templates = [];

    /**
     * @param list<array{class?: string, function?: string, file?: string, line?: int, object?: object}> $trace
     *
     * @return list<array{class?: string, function?: string, file?: string, line?: int}>
     */
    public function map(array $trace): array
    {
        foreach ($trace as $index => $frame) {
            $caller = $trace[$index + 1] ?? [];
            $class = $caller['class'] ?? null;

            unset($trace[$index]['object']);

            if (!isset($frame['file'], $frame['line'], $class)) {
                continue;
            }

            $template = $caller['object'] ?? $this->createTemplate($class);
            if (!$template instanceof Template) {
                continue;
            }

            if ((new \ReflectionObject($template))->getFileName() !== $frame['file']) {
                continue;
            }

            $line = $this->getTemplateLine($template, $frame['line']);
            if (null === $line) {
                continue;
            }

            $source = $template->getSourceContext();
            $trace[$index]['file'] = $source->getPath() ?: $source->getName();
            $trace[$index]['line'] = $line;
        }

        return $trace;
    }

    /**
     * Returns the template line matching a line of the compiled template, or
     * null when the line is not part of the line map.
     */
    private function getTemplateLine(Template $template, int $compiledLine): ?int
    {
        foreach ($template->getDebugInfo() as $codeLine => $templateLine) {
            if ($codeLine <= $compiledLine) {
                return $templateLine;
            }
        }

        return null;
    }

    /**
     * Creates an instance of a compiled template class without running its
     * constructor, as the debug info and the source context do not depend on
     * the state of the template.
     */
    private function createTemplate(string $class): ?Template
    {
        if (!is_subclass_of($class, Template::class)) {
            return null;
        }

        return $this->templates[$class] ??= (new \ReflectionClass($class))->newInstanceWithoutConstructor();
    }
}
