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
 *
 * In the frames of the methods of a compiled template, the compiled class name
 * is replaced with the template name, the type with "%", and the block methods
 * with "block <name>". For instance "post/show.html.twig%block main".
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
     * @return list<array{class?: string, function?: string, type?: string, file?: string, line?: int}>
     */
    public function map(array $trace): array
    {
        foreach ($trace as $index => $frame) {
            $caller = $trace[$index + 1] ?? [];
            $class = $caller['class'] ?? null;

            unset($trace[$index]['object']);

            $this->renameTemplateFrame($trace[$index], $frame);

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
     * @param array<string, mixed> $mapped
     * @param array<string, mixed> $frame
     */
    private function renameTemplateFrame(array &$mapped, array $frame): void
    {
        $class = $frame['class'] ?? null;
        if (null === $class) {
            return;
        }

        $template = $frame['object'] ?? $this->createTemplate($class);
        if (!$template instanceof Template || $template::class !== $class) {
            return;
        }

        $name = $template->getTemplateName();
        $function = $frame['function'] ?? '';

        $mapped['class'] = $name;
        $mapped['type'] = '%';
        $mapped['function'] = str_starts_with($function, 'block_')
            ? 'block '.substr($function, \strlen('block_'))
            : str_replace($class, $name, $function);
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
