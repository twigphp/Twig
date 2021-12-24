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

/**
 * @author Koala Yeung <koalay@gmail.com>
 */
final class StacktraceLogger
{
    /**
     * Log message with stacktrace to server log.
     *
     * With error_log, tell server admin something they need to know with full stacktrace.
     *
     * @param string  $message  The message to log with the backtrace.
     * @param integer $offset   The number of top layers of the stack to ignore.
     * @param integer $options  The $options parameter passed to debug_backtrace().
     * @param integer $limit    The $limit parameter passed to debug_backtrace().
     *
     * @return void
     */
    public static function log(string $message, int $offset = 0, int $options = DEBUG_BACKTRACE_PROVIDE_OBJECT, int $limit = 0)
    {
        // generate stack, then remove the layer created from
        // this function.
        if ($limit !== 0) $limit += 1;
        $stack = \debug_backtrace($options, $limit);
        if ($offset > sizeof($stack)) return '';
        array_splice($stack, 0, $offset+1);

        // format the message.
        $backtrace_str = StacktraceLogger::formatBacktrace($stack);
        $top = array_shift($stack);
        error_log("{$message} in {$top['file']}:{$top['line']}\nStacktrace:\n{$backtrace_str}");
    }

    /**
     * Fomrats a debug_backtrace output.
     *
     * Mimicing the output of debug_print_backtrace() without actually calling it.
     *
     * @param array $stack Supposedly the debug_backtrace() output.
     *
     * @return string The formatted message.
     */
    private static function formatBacktrace(array $stack): string
    {
        return implode("\n", array_map(function ($num, $layer) {
            return "#{$num} {$layer['function']}() called at [{$layer['file']}:{$layer['line']}]";
        }, array_keys($stack), array_values($stack))) ?? '';
    }
}