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
 * The frames of the methods of a compiled template also get a "template" key
 * with the template name, and a "block" key with the block name for the block
 * methods. The other keys of the frames are left untouched.
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
     * @return list<array{class?: string, function?: string, file?: string, line?: int, object?: object, template?: string, block?: string}>
     */
    public function map(array $trace): array
    {
        foreach ($trace as $index => $frame) {
            $caller = $trace[$index + 1] ?? [];
            $class = $caller['class'] ?? null;

            $this->addTemplateInfo($trace[$index], $frame);

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
    private function addTemplateInfo(array &$mapped, array $frame): void
    {
        $class = $frame['class'] ?? null;
        if (null === $class) {
            return;
        }

        $template = $frame['object'] ?? $this->createTemplate($class);
        if (!$template instanceof Template || $template::class !== $class) {
            return;
        }

        $mapped['template'] = $template->getTemplateName();

        $function = $frame['function'] ?? '';
        if (str_starts_with($function, 'block_')) {
            $mapped['block'] = substr($function, \strlen('block_'));
        }
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
