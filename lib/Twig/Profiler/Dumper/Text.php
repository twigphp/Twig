<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class Twig_Profiler_Dumper_Text extends \Twig\Profiler\Dumper\BaseDumper
{
    protected function formatTemplate(\Twig\Profiler\Profile $profile, $prefix)
    {
        return sprintf('%s└ %s', $prefix, $profile->getTemplate());
    }

    protected function formatNonTemplate(\Twig\Profiler\Profile $profile, $prefix)
    {
        return sprintf('%s└ %s::%s(%s)', $prefix, $profile->getTemplate(), $profile->getType(), $profile->getName());
    }

    protected function formatTime(\Twig\Profiler\Profile $profile, $percent)
    {
        return sprintf('%.2fms/%.0f%%', $profile->getDuration() * 1000, $percent);
    }
}

class_alias('Twig_Profiler_Dumper_Text', 'Twig\Profiler\Dumper\TextDumper', false);
