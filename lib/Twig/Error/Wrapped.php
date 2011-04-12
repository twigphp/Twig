<?php

/*
 * This file is part of Twig.
 *
 * (c) 2011 Fabien Potencier
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
class Twig_Error_Wrapped extends Twig_Error_Runtime
{
    public function __construct(Exception $e)
    {
        if ($e instanceof Twig_Error && -1 !== $e->getTemplateLine() && null === $e->getTemplateFile()) {
            parent::__construct($e->getMessage(), $e->getTemplateLine(), $e->getTemplateFile(), $e);
        } else {
            list($lineno, $filename) = $this->findTemplateInfo($e);

            parent::__construct($e->getMessage(), $lineno, $filename, $e);
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
